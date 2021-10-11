<?php

namespace Test\FinancialApiBundle\Company;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * @package Test\FinancialApiBundle\Company
 */
class ActivityTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }


    function testGetActivities(): void
    {
        $resp = $this->rest(
            'GET',
            '/public/v4/activities',
            [],
            [],
            200
        );

        self::assertObjectHasAttribute("id", $resp[0]);
        self::assertObjectHasAttribute("name", $resp[0]);
        self::assertObjectHasAttribute("name_es", $resp[0]);
        self::assertObjectHasAttribute("name_ca", $resp[0]);
    }

}
