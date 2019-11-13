<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class MailingTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class MailingTest extends BaseApiTest {

    private $accounts;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $mailer = $this->createMock(\Swift_Mailer::class)
            ->method('send')
            ->withAnyParameters()
            ->willReturn(1);

        $this->client->getContainer()->set('mailer', $mailer);

        $resp = $this->requestJson('GET', '/admin/v3/accounts');
        $this->accounts = json_decode($resp->getContent())->data->elements;
    }

    /**
     * @throws \Exception
     */
    function testCreateAttachAndSendMailing(){
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

        foreach ($this->accounts as $account) {
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

        $output = $this->runCommand('rec:mailing:send');
        self::assertRegExp("/Processing 0 mailings/", $output);

        $route = '/admin/v3/mailings/' . $mailing->id;
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            Response::HTTP_OK,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $mailing = json_decode($resp->getContent())->data;
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

        $output = $this->runCommand('rec:mailing:send');
        self::assertRegExp("/Processing 1 mailings/", $output);
    }
}
