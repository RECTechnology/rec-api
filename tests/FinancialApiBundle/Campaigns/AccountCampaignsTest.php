<?php

namespace Test\FinancialApiBundle\Campaigns;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class AccountCampaignsTest
 * @package Test\FinancialApiBundle\Campaign
 */
class AccountCampaignsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testIndex()
    {
        $resp = $this->requestJson('GET', '/user/v3/account_campaign');
        self::assertEquals(
            403,
            $resp->getStatusCode()
        );

    }

}
