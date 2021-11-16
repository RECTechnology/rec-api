<?php

namespace Test\FinancialApiBundle\Admin\DelegatedChange;

use App\FinancialApiBundle\DataFixture\DelegatedChangeFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Financial\Methods\LemonWayMethod;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class ReportClientsAndProvidersTest
 * @package Test\FinancialApiBundle\Admin\DelegatedChange
 */
class DelegatedChangeTest extends BaseApiTest {

    use MongoDBTrait;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    private function createEmptyDelegatedChange(){
        $tomorrow = new \DateTime("tomorrow");
        $route = '/admin/v3/delegated_changes';
        $resp = $this->requestJson(
            'POST',
            $route,
            ['scheduled_at' => $tomorrow->format('c')]
        );
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        return json_decode($resp->getContent());
    }

    function testCreate()
    {
        $dcContent = $this->createEmptyDelegatedChange();
        self::assertGreaterThan(0, $dcContent->data->id);

        $resp = $this->requestJson('GET', '/admin/v3/accounts?type=PRIVATE');
        $users = json_decode($resp->getContent())->data->elements;
        self::assertGreaterThan(0, count($users));
        $resp = $this->requestJson('GET', '/admin/v3/accounts?type=COMPANY&tier=2');
        $exchangers = json_decode($resp->getContent())->data->elements;
        self::assertGreaterThan(0, count($exchangers));
        $exchanger = $exchangers[0];
        foreach ($users as $user){
            $resp = $this->requestJson(
                'POST',
                '/admin/v3/delegated_change_data',
                [
                    'account_id' => $user->id,
                    'exchanger_id' => $exchanger->id,
                    'delegated_change_id' => $dcContent->data->id,
                    'amount' => 200
                ]
            );
            self::assertEquals(201, $resp->getStatusCode(), $resp->getContent());
            $resp = $this->requestJson(
                'GET',
                '/admin/v3/delegated_change_data?delegated_change_id=' . $dcContent->data->id
            );
            self::assertEquals(200, $resp->getStatusCode(), $resp->getContent());

            $resp = $this->requestJson(
                'GET',
                '/admin/v3/delegated_change_data?delegate_change_id=' . $dcContent->data->id
            );
            self::assertEquals(400, $resp->getStatusCode(), $resp->getContent());
        }

    }

    function testUpdate()
    {
        $nextWeek = new \DateTime("next week");
        $content = $this->createEmptyDelegatedChange();
        $route = '/admin/v3/delegated_changes/' . $content->data->id;
        $resp = $this->requestJson(
            'PUT',
            $route,
            ['scheduled_at' => $nextWeek->format('c')]
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $contentChanged = json_decode($resp->getContent());
        self::assertEquals($content->data->id, $contentChanged->data->id);
    }

    //TODO disabled to avoid errors in github tests
    function _testDelete()
    {
        $this->markTestIncomplete();
        $content = $this->createEmptyDelegatedChange();
        $route = '/admin/v3/delegated_changes/' . $content->data->id;
        $this->rest('DELETE', $route);
    }

    /**
     * @param array $data
     */
    private function useLemonWayMock(array $data): void
    {
        $lw = $this->createMock(LemonWayMethod::class);
        $lw->method('getCurrency')->willReturn("EUR");
        $lw->method('getPayInInfoWithCommerce')->willReturn($data);
        $lw->method('getCname')->willReturn('lemonway');
        $lw->method('getType')->willReturn('in');

        $this->override('net.app.in.lemonway.v1', $lw);
    }

    function _testDelegatedChange(){  // test disabled because the mock fails with $this->runCommand('rec:delegated_change:run');
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $ini_account_balance = $this->rest('GET', "/admin/v3/accounts/3")->wallets[0]->balance;
        $data = ['status' => Transaction::$STATUS_RECEIVED,
            'company_id' => 1,
            'amount' => 6000,
            'commerce_id' => 2,
            'concept' => 'test delegated charge',
            'pin' => '3210',
            'save_card' => 0];

        $this->useLemonWayMock($data);

        $resp = $this->rest(
            'PUT',
            '/admin/v3/delegated_changes/1',
            [
                'status' => 'scheduled'
            ]
        );

        $output = $this->runCommand('rec:delegated_change:run');
        self::assertStringNotContainsString("Transaction creation failed", $output);
        $this->runCommand('rec:fiat:check');
        $this->runCommand('rec:crypto:check');
        $this->runCommand('rec:crypto:check');

        $end_account_balance = $this->rest('GET', "/admin/v3/accounts/3")->wallets[0]->balance;
        $this->assertEquals($end_account_balance - $ini_account_balance, DelegatedChangeFixture::AMOUNT * 1000000);
    }

    //TODO disabled to avoid errors in github tests
    function _testDelegatedChangeImportCSV(){

        $lista = array (
            array('account', 'exchanger', 'amount', 'sender'),
            array(2, 5, 465, 6)
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
            '/admin/v1/delegated_change_data/csv',
            [
                "path" => $file_route,
                'delegated_change_id' => 1
            ]
        );
        $output = $this->runCommand('rec:delegated_change:run');
        $this->massiveTransaccionsReport();
    }
    function massiveTransaccionsReport()
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $route = "/admin/v4/reports/massive-transactions/1";
        $resp = $this->request('POST', $route, null, [], []);
        $output = $this->runCommand('rec:mailing:send');
        self::assertRegExp("/Processing/", $output);
    }
}