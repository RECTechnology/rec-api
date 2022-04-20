<?php

namespace Test\FinancialApiBundle\Admin;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class AdminApiTest
 * @package Test\FinancialApiBundle\Admin
 */
abstract class AdminApiTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }
}