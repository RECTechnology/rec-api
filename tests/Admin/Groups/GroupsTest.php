<?php

namespace App\Tests\Admin\Groups;

use App\DataFixtures\AccountFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\BaseApiTest;

/**
 * Class GroupsTest
 * @package App\Tests\Admin\Users
 */
class GroupsTest extends BaseApiTest {

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
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
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
            'kyc_manager_id' => 2,
            'prefix' => '34'
        );
        $resp = $this->requestJson('PUT', $route, $values);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
        );

    }

    function testUpdatePrefixBadFormatShouldFail()
    {
        $route = '/admin/v3/accounts/1';
        $values = array(
            'kyc_manager_id' => 2,
            'prefix' => '+34'
        );
        $resp = $this->requestJson('PUT', $route, $values);
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "Invalid prefix format"
        );

        $content = json_decode($resp->getContent(),true);
        $errors = $content['errors'];
        $errorMessage = $errors[0]['message'];
        self::assertEquals("Invalid prefix format", $errorMessage);

    }

    function testUpdateAccountRezeroB2BAccessShouldWork()
    {
        /** @var EntityManagerInterface $em */
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Group $account */
        $account = $em->getRepository(Group::class)->findOneBy(['name' => AccountFixtures::TEST_ACCOUNT_REZERO_2['name']]);
        $route = '/admin/v3/accounts/'.$account->getId();
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
