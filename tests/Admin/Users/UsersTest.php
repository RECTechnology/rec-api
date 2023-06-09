<?php

namespace App\Tests\Admin\Users;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\BaseApiTest;

/**
 * Class TranslationsTest
 * @package App\Tests\Admin\Users
 */
class UsersTest extends BaseApiTest {

    const USER_REQUIRED_FIELDS = [
        'id',
        'username',
        'email',
        'dni',
        'expired',
        'name',
        'locked',
        'enabled'
    ];

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testUsersReturnsAllRequiredFields()
    {
        $route = '/admin/v3/users';
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
        $user = $data['elements'][0];

        foreach (self::USER_REQUIRED_FIELDS as $field){
            self::assertArrayHasKey($field, $user);
        }
    }

    function testSingleUserReturnsAllRequiredFields()
    {
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $targetUserData = UserFixtures::TEST_USER_LOCKED_CREDENTIALS;
        /** @var User $targetUser */
        $targetUser = $em->getRepository(User::class)->findOneBy(['username' => $targetUserData['username']]);


        $route = '/admin/v3/users/'.$targetUser->getId();
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);

        self::assertArrayHasKey('data', $content);
        $user = $content['data'];

        foreach (self::USER_REQUIRED_FIELDS as $field){
            self::assertArrayHasKey($field, $user);
        }

        self::assertEquals(false, $user['enabled']);
    }
}
