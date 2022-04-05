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
use DateTime;
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

    /**
     * @requires extension mysqli
     */
    function testTxBlockImportKoCSV(){
        $this->markTestIncomplete();
        $tb_id = 2;
        $lista = array (
            array('sender', 'exchanger', 'account', 'amount'),
            array(6, 5, 5, 10),
            array(6, 5, 5, 11),
            array(6, 5, 9, 10000000),
            array(6, 5, 200, 465)
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
        $tb = $em->getRepository(DelegatedChange::class)->find($tb_id);
        self::assertEquals($file_route, $tb->getUrlCsv());
        self::assertEquals('pending_validation', $tb->getStatus());

        //run validator command
        $this->runCommand('rec:transaction_block:validate');
        $tb = $em->getRepository(DelegatedChange::class)->find($tb_id);
        self::assertEquals(DelegatedChange::STATUS_INVALID, $tb->getStatus());
        self::assertEquals(4, $tb->getStatistics()["scheduled"]["warnings"]);
        $logs = $em->getRepository(TransactionBlockLog::class)->findBy(['block_txs' => $tb_id]);
        self::assertCount(10, $logs);

    }

    function testTxBlockImportOkCSV(){
        $this->markTestIncomplete();
        $tb_id = 2;
        $lista = array (
            array(' sender', 'exchanger ', ' account', 'amount '),
            array(6, 5, 2 , 10),
            array(6, 8, 2, 465)
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
        $tb = $em->getRepository(DelegatedChange::class)->find($tb_id);
        self::assertEquals($file_route, $tb->getUrlCsv());
        self::assertEquals('pending_validation', $tb->getStatus());

        //run validator command
        $this->runCommand('rec:transaction_block:validate');
        $tb = $em->getRepository(DelegatedChange::class)->find($tb_id);
        self::assertEquals(DelegatedChange::STATUS_DRAFT, $tb->getStatus());
        self::assertEquals(2, $tb->getStatistics()["scheduled"]["warnings"]);

        $errors = $em->getRepository(TransactionBlockLog::class)->findBy(['block_txs' => $tb_id, 'type' => TransactionBlockLog::TYPE_ERROR]);
        self::assertCount(0, $errors);


        $tb->setStatus(DelegatedChange::STATUS_SCHEDULED);
        $tb->setScheduledAt(new DateTime('tomorrow'));
        $em->flush();

        //run executor command
        $this->runCommand('rec:transaction_block:execute');
        $resp = json_decode($this->requestJson('GET', '/admin/v3/delegated_changes?id='.$tb_id)->getContent())->data->elements[0];
        self::assertEquals(DelegatedChange::STATUS_SCHEDULED, $resp->status);
        self::assertEquals(0, $resp->statistics->result->success_tx);
        self::assertEquals(0, $resp->statistics->result->failed_tx);


        $tb->setScheduledAt(new DateTime('yesterday'));
        $em->flush();

        //run executor command
        $this->runCommand('rec:transaction_block:execute');
        $resp = json_decode($this->requestJson('GET', '/admin/v3/delegated_changes?id='.$tb_id)->getContent())->data->elements[0];
        self::assertEquals(DelegatedChange::STATUS_FINISHED, $resp->status);
        self::assertEquals($resp->statistics->scheduled->tx_to_execute, $resp->statistics->result->success_tx);
        self::assertEquals(0, $resp->statistics->result->failed_tx);

    }
}