<?php

namespace Test\FinancialApiBundle\Bugs;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Symfony\Component\HttpFoundation\Response;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class ValidationErrorsShouldBeAsExpected
 * @package Test\FinancialApiBundle\Bugs
 */
class ValidationErrorsShouldBeAsExpectedTest extends BaseApiTest {

    private $mailing;
    private $account;

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/admin/v3/mailings';
        $resp = $this->requestJson('POST', $route);
        self::assertEquals(Response::HTTP_CREATED, $resp->getStatusCode());
        $this->mailing = json_decode($resp->getContent())->data;
        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('get', $route);
        self::assertEquals(Response::HTTP_OK, $resp->getStatusCode());
        $accounts = json_decode($resp->getContent())->data->elements;
        self::assertGreaterThan(0, count($accounts));
        $this->account = $accounts[0];
    }

    function testValidationErrorsShouldBeJsonArray() {

        $route = '/admin/v3/mailing_deliveries';
        $params = ['account_id' => $this->account->id, 'mailing_id' => $this->mailing->id];
        $resp = $this->requestJson('POST', $route, $params);
        self::assertEquals(
            Response::HTTP_CREATED,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }
}
