<?php

namespace Test\FinancialApiBundle\Admin\Campaign;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\Admin\AdminApiTest;


/**
 * Class CampaignTest
 * @package Test\FinancialApiBundle\Admin\Campaign
 */
class CampaignTest extends AdminApiTest {


    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testIndex()
    {
        $resp = $this->requestJson('GET', '/admin/v3/campaigns');
        self::assertEquals(
            200,
            $resp->getStatusCode()
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];

        self::assertArrayNotHasKey('accounts', $elements[0]);
    }

    function testCreateCampaignV2(){
        $start = new \DateTime();
        $finish = new \DateTime('+3 days');
        $data = array(
            'init_date' => $start->format('Y-m-d\TH:i:sO'),
            'end_date' => $finish->format('Y-m-d\TH:i:sO'),
            'bonus_enabled' => 1,
            'name' => 'Random name',
            'tos' => 'kljhbih',
            'balance' => 0
        );
        $resp = $this->requestJson('POST', '/admin/v3/campaigns', $data);

        self::assertEquals(
            201,
            $resp->getStatusCode()
        );
        $response = json_decode($resp->getContent(),true);

        $created_data = $response['data'];
        self::assertArrayHasKey('code', $created_data);
        self::assertNotNull($created_data['code']);
    }

    function testDeleteCampaignWithOutAccountsJoinedInShouldWork(){
        $resp = $this->requestJson('DELETE', '/admin/v3/campaigns/4');
        self::assertEquals(
            204,
            $resp->getStatusCode()
        );
    }

    function testDeleteCampaignWithAccountsJoinedInShouldFail(){
        $resp = $this->requestJson('DELETE', '/admin/v3/campaigns/3');
        self::assertEquals(
            403,
            $resp->getStatusCode()
        );
    }

}