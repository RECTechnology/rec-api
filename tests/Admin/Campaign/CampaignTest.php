<?php

namespace App\Tests\Admin\Campaign;

use App\DataFixtures\UserFixtures;
use App\Entity\Campaign;
use App\Tests\Admin\AdminApiTest;


/**
 * Class CampaignTest
 * @package App\Tests\Admin\Campaign
 */
class CampaignTest extends AdminApiTest
{


    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_ADMIN_CREDENTIALS);
    }

    function testIndex()
    {
        $resp = $this->requestJson('GET', '/admin/v3/campaigns');
        self::assertEquals(
            200,
            $resp->getStatusCode()
        );

        $content = json_decode($resp->getContent(), true);
        $elements = $content['data']['elements'];

        self::assertArrayNotHasKey('accounts', $elements[0]);
    }

    function testCreateCampaignV2()
    {
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
        $response = json_decode($resp->getContent(), true);

        $created_data = $response['data'];
        self::assertArrayHasKey('code', $created_data);
        self::assertNotNull($created_data['code']);
    }

    function testDeleteCampaignWithOutAccountsJoinedInShouldWork()
    {
        $resp = $this->requestJson('DELETE', '/admin/v3/campaigns/4');
        self::assertEquals(
            204,
            $resp->getStatusCode()
        );
    }

    function testDeleteCampaignWithAccountsJoinedInShouldFail()
    {
        $resp = $this->requestJson('DELETE', '/admin/v3/campaigns/3');
        self::assertEquals(
            403,
            $resp->getStatusCode()
        );
    }

    function testListUsersByCampaignShouldWork()
    {
        $resp = $this->requestJson('GET', '/admin/v1/campaign/3/users');
        self::assertEquals(
            200,
            $resp->getStatusCode()
        );

        $content = json_decode($resp->getContent(), true);

        $elements = $content['data']['elements'];
        self::assertArrayHasKey('total_accumulated_bonus', $content['data']);
        self::assertArrayHasKey('total_spent_bonus', $content['data']);
        foreach ($elements as $element) {
            self::assertArrayHasKey('accumulated_bonus', $element);
            self::assertArrayHasKey('spent_bonus', $element);
        }
    }

    function testExportUsersByCampaignShouldWork()
    {
        $resp = $this->request('GET', '/admin/v1/campaign/3/users/export');
        self::assertEquals(
            200,
            $resp->getStatusCode()
        );
    }

    function testSearchByStatusesShouldWork(){
        $status_array = [
            Campaign::STATUS_ACTIVE,
            Campaign::STATUS_FINISHED
        ];
        $resp = $this->requestJson('GET', "/admin/v3/campaigns/search?statuses[]=finished&statuses[]=active");
        self::assertEquals(
            200,
            $resp->getStatusCode()
        );
        $content = $resp->getContent();
        $data = json_decode($content,true);
        foreach ($data['data']['elements'] as $element){
            self::assertContains($element['status'],$status_array);
        }
    }

    function testSearchUsernameOnAccountCampaignsShouldWork(){
        $username = UserFixtures::TEST_USER_CREDENTIALS['username'];

        $resp = $this->requestJson('GET', '/admin/v3/account_campaigns/search?search='.$username);

        self::assertEquals(
            200,
            $resp->getStatusCode()
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];
        foreach ($elements as $element){
            self::assertEquals($username, $element['account']['kyc_manager']['username']);
        }
    }
}