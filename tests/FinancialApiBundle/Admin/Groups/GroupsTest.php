<?php

namespace Test\FinancialApiBundle\Admin\Groups;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\User;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class GroupsTest
 * @package Test\FinancialApiBundle\Admin\Users
 */
class GroupsTest extends BaseApiTest {

    use MongoDBTrait;
    const ACCOUNT_REQUIRED_FIELDS = [
        'id',
        'name',
        'kyc_manager',
        'company_image',
        'rec_address',
        'wallets',
        'cif',
        'email',
        'tier'
    ];

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testGetAccounts()
    {
        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];
        self::assertArrayHasKey('elements', $data);
        self::assertGreaterThan(0, count($data['elements']));
        $account = $data['elements'][0];

        foreach (self::ACCOUNT_REQUIRED_FIELDS as $field){
            self::assertArrayHasKey($field, $account);
        }
    }

    function testUpdateAccountShouldWork()
    {
        $route = '/admin/v3/accounts/1';
        $values = array(
            'kyc_manager_id' => 2
        );
        $resp = $this->requestJson('PUT', $route, $values);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
        );

    }

    function testUpdateAccountRezeroB2BAccessShouldWork()
    {
        $route = '/admin/v3/accounts/1';
        $values = array(
            'rezero_b2b_access' => 'granted'
        );
        $resp = $this->requestJson('PUT', $route, $values);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
        );
        $content = json_decode($resp->getContent());
        self::assertEquals('granted', $content->data->rezero_b2b_access);

    }

    function testUpdateRelatedEntitiesInAccountWithoutIdShouldFail()
    {
        $route = '/admin/v3/accounts/1';
        $values = array(
            'kyc_manager' => 2
        );
        $resp = $this->requestJson('PUT', $route, $values);

        self::assertEquals(
            400,
            $resp->getStatusCode(),
        );

        $content=json_decode($resp->getContent(),true);

        self::assertStringContainsString("Use suffix '_id' to set related properties", $content['message']);

    }
    function testGetExchangers()
    {
        $route = '/user/v1/wallet/exchangers';
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        foreach ($content["data"]["elements"] as $company) {
            self::assertEquals('KYC2', $company["kyc"]);
        }
    }

    function testGetGroups()
    {
        $route = '/manager/v1/groups';
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];
        self::assertArrayHasKey('elements', $data);
        self::assertGreaterThan(0, count($data['elements']));

    }

    function testGetGroupsV2()
    {
        $route = '/manager/v2/groups';
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];
        self::assertArrayHasKey('elements', $data);
        self::assertGreaterThan(0, count($data['elements']));

    }
}
