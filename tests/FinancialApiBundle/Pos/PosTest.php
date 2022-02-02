<?php

namespace Test\FinancialApiBundle\Pos;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class PosTest
 * @package Test\FinancialApiBundle\Pos
 */
class PosTest extends BaseApiTest {

    const ROUTE = "/user/v3/pos";


    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
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
