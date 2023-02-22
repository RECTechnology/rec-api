<?php

namespace App\Tests\Campaigns;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class CampaignsTest
 * @package App\Tests\Campaign
 */
class CampaignsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testIndex()
    {
        $roles = ['user', 'public'];
        foreach ($roles as $role){
            if($role === 'public'){
                $this->signOut();
            }
            $resp = $this->requestJson('GET', '/'.$role.'/v3/campaigns');
            self::assertEquals(
                200,
                $resp->getStatusCode()
            );

            $content = json_decode($resp->getContent(),true);
            $elements = $content['data']['elements'];

            self::assertArrayNotHasKey('accounts', $elements[0]);
        }

    }

}
