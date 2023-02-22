<?php

namespace App\Tests\Admin;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class AdminApiTest
 * @package App\Tests\Admin
 */
abstract class AdminApiTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }
}