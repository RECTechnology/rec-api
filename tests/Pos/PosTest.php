<?php

namespace App\Tests\Pos;

use App\DataFixtures\UserFixtures;
use App\Tests\BaseApiTest;

/**
 * Class PosTest
 * @package App\Tests\Pos
 */
class PosTest extends BaseApiTest {

    const ROUTE = "/user/v3/pos";


    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixtures::TEST_USER_CREDENTIALS);
    }

    function testListAllPosShouldListOnlyOwnedPos(){

        $user = $this->getSignedInUser();
        $account = $user->group_data;
        $list = $this->requestJson('GET', self::ROUTE);

        $data = json_decode($list->getContent(),true);
        $elements = $data['data']['elements'];
        foreach ($elements as $element){
            self::assertEquals($account->id, $element['account']['id']);
        }

    }

    function testCreateShouldFail()
    {
        $user = $this->getSignedInUser();
        $account = $user->group_data;
        $pos = $this->createPos($account);

    }

    private function createPos($account)
    {
        return $this->rest('POST', self::ROUTE, [
            'account_id' => $account->id
        ], [], 403);
    }

}
