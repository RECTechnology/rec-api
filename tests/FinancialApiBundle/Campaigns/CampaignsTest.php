<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

/**
 * Class CampaignsTest
 * @package Test\FinancialApiBundle\Campaign
 */
class CampaignsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testIndex()
    {
        $resp = $this->requestJson('GET', '/user/v3/campaigns');
        self::assertEquals(
            200,
            $resp->getStatusCode()
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];

        self::assertArrayNotHasKey('accounts', $elements[0]);
    }

}
