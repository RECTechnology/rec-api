<?php

namespace App\Tests\Admin\B2B;

use App\DataFixtures\UserFixtures;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\BaseApiTest;

/**
 * Class MailingTest
 * @package App\Tests\Admin\B2B
 */
class MailingTest extends BaseApiTest {

    private $accounts;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);

        $this->accounts = $this->rest('GET', '/admin/v3/accounts');
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
        self::assertMatchesRegularExpression("/Processing 0 mailings/", $output);

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
        self::assertMatchesRegularExpression("/Processing 1 mailings/", $output);
    }
}
