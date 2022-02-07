<?php

namespace Test\FinancialApiBundle\Admin\TransactionBlocks;

use App\FinancialApiBundle\DataFixture\DelegatedChangeFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\TransactionBlockLog;
use App\FinancialApiBundle\Financial\Methods\LemonWayMethod;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class TransactionBlocksTest
 * @package Test\FinancialApiBundle\Admin\TransactionBlocks
 */
class TransactionBlocksTest extends BaseApiTest {

    use MongoDBTrait;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testTxBlockImportCSV(){
        $this->markTestIncomplete();
        $tb_id = 1;
        $lista = array (
            array('account', 'exchanger', 'amount', 'sender'),
            array(2, 5, 10, 6),
            array(9, 5, 10000000, 6),
            array(2000, 5, 465, 6)
        );

        $fp = fopen('/opt/project/var/cache/file.csv', 'w');

        foreach ($lista as $campos) {
            fputcsv($fp, $campos);
        }

        fclose($fp);
        $fp = new UploadedFile('/opt/project/var/cache/file.csv', 'file.csv', "text/csv");
        $resp = $this->request(
            'POST',
            '/user/v1/upload_file',
            '',
            [],
            [],
            ["file" => $fp]
        );

        $file_route = simplexml_load_string($resp->getContent(), "SimpleXMLElement", LIBXML_NOCDATA)->data->entry[0]->__tostring();
        $file_route = "/opt/project/web/static".$file_route;
        $resp = $this->rest(
            'POST',
            '/admin/v1/txs_block/csv',
            [
                "path" => $file_route,
                'delegated_change_id' => $tb_id
            ]
        );
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $tb = $em->getRepository(DelegatedChange::class)->find(1);
        self::assertEquals($file_route, $tb->getUrlCsv());
        self::assertEquals('pending_validation', $tb->getStatus());

        //run command
        $this->runCommand('rec:transaction_block:validate');
        $tb = $em->getRepository(DelegatedChange::class)->find($tb_id);
        self::assertEquals(DelegatedChange::STATUS_INVALID, $tb->getStatus());
        $logs = $em->getRepository(TransactionBlockLog::class)->findBy(['block_txs' => $tb_id]);
        self::assertCount(3, $logs);

    }
}