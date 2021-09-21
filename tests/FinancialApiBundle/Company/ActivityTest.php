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
            '/app/v4/activities',
            [],
            [
                'Content-Type' => 'application/json',
                'Authorization' => $this->token
            ],
            200
        );
    }

}
