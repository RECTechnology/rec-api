<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class MailingShouldBeLocalizedTest
 * @package Test\FinancialApiBundle\Bugs
 */
class MailingShouldBeLocalizedTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function createMailing(){
        $route = '/admin/v3/mailings';
        $resp = $this->requestJson(
            'POST',
            $route,
            [
                'subject' => 'test subject',
                'content' => 'test content',
                'attachments' => [
                    "b2b_report.pdf" => "b2b_report",
                    "sapo.jpg" => "http://static.malaga.es/malaga/subidas/imagenes/8/0/arc_312408_v2_g.jpg"
                ]
            ]
        );
        self::assertEquals(
            Response::HTTP_CREATED,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $mailing = json_decode($resp->getContent())->data;

        $route = '/admin/v3/mailings/' . $mailing->id;
        $resp = $this->requestJson(
            'PUT',
            $route,
            [
                'subject' => 'test subject ES',
                'content' => 'test content ES',
            ],
            ['HTTP_Content-Language' => 'es']
        );

        self::assertEquals(
            Response::HTTP_OK,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );


        return $mailing;
    }

    function addAccountToMailing($mailing, $account){

        $route = '/admin/v3/mailing_deliveries';
        $resp = $this->requestJson(
            'POST',
            $route,
            [
                'account_id' => $account->id,
                'mailing_id' => $mailing->id
            ]
        );
        self::assertEquals(
            Response::HTTP_CREATED,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function getUserAccount(){

        $route = '/user/v1/account';
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        return json_decode($resp->getContent())->data->group_data;
    }

    function scheduleMailing($mailing){

        $route = '/admin/v3/mailings/' . $mailing->id;
        $now = new \DateTime();
        $resp = $this->requestJson(
            'PUT',
            $route,
            [
                'status' => 'scheduled',
                'scheduled_at' => $now->format('c')
            ]
        );
        self::assertEquals(
            Response::HTTP_OK,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function sendMailing(){
        $output = $this->runCommand('rec:mailing:send');
        self::assertMatchesRegularExpression("/Processing 1 mailings/", $output);
    }

    function testMailIsDeliveredLocalized()
    {
        $mailing = $this->createMailing();
        $account = $this->getUserAccount();
        $this->addAccountToMailing($mailing, $account);
        $this->scheduleMailing($mailing);
        $this->sendMailing();
    }
}
