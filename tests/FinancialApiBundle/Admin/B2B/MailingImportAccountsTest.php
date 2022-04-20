<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class MailingImportAccountsTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class MailingImportAccountsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    private function createMailing(){
        $route = '/admin/v3/mailings';
        return $this->rest(
            'POST',
            $route,
            [
                'subject' => 'test subject',
                'content' => 'test content'
            ]
        );
    }

    private function getAllAccounts(){
        return $this->rest('GET', '/admin/v3/accounts');
    }

    function testCreateAndImportDeliveries(){

        $mailing = $this->createMailing();
        $accounts = $this->getAllAccounts();

        $csv = "account_id;mailing_id\n";
        foreach ($accounts as $account) {
            $csv .= "{$account->id};{$mailing->id}\n";
        }

        $fm = $this->createMock(UploadManager::class);
        $fm->method('readFileUrl')->willReturn($csv);
        $this->inject('file_manager', $fm);

        $route = '/admin/v3/mailing_deliveries/import';
        $this->rest(
            'POST',
            $route,
            ['csv' => 'ignored']
        );

        $mailing = $this->rest('GET', "/admin/v3/mailing/{$mailing->id}");

        self::assertSameSize($accounts, $mailing->deliveries);
    }


    function testCreateAndImportBadData(){
        $csv  = "account_id;mailing_id\n";
        $csv .= "9999;9999\n";

        $fm = $this->createMock(UploadManager::class);
        $fm->method('readFileUrl')->willReturn($csv);
        $this->inject('file_manager', $fm);

        $route = '/admin/v3/mailing_deliveries/import';
        $resp = $this->requestJson(
            'POST',
            $route,
            ['csv' => 'ignored']
        );

        self::assertEquals(Response::HTTP_BAD_REQUEST, $resp->getStatusCode());
    }


}
