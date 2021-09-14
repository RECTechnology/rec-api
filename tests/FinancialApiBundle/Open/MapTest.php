<?php

namespace Test\FinancialApiBundle\Open;

use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Offer;
use DateTime;
use Faker\Factory;
use Test\FinancialApiBundle\BaseApiTest;

class MapTest extends BaseApiTest {

    public function testMapSearchResponds200(){
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $content = [
            'offset' => 0,
            'limit' => 300,
            'sort' => 'name',
            'order' => 'DESC',
            'DESC' => 'name',
            'query' => [
                'search',
                'on_map'=> true,
                'only_with_offers' => '!!filters.checkbox_Offers',
                'type' => 'COMPANY',
                'subtype'
            ]
        ];
        $response = $this->requestJson('GET', '/user/v4/accounts/search', $content);
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }

    public function testMapSearchOnlyWithOffersResponds200(){

        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $response = $this->requestJson('GET', '/user/v4/accounts/search?only_with_offers=true');

        self::assertEquals(
            200,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );

        $accounts = json_decode($response->getContent(),true);
        self::assertEquals(3, count($accounts));

        foreach ($accounts['data']['elements'] as $account){
            self::assertEquals(1, $account['has_offers']);
        }

    }
}