<?php

namespace Test\FinancialApiBundle\Open;

use Faker\Factory;
use Test\FinancialApiBundle\BaseApiTest;

class RegisterTest extends BaseApiTest {

    public function testRegisterResponds201(){
        $client = $this->getApiClient();
        $faker = Factory::create();

        $content = json_encode([
            'username' => $faker->userName
        ]);

        $client->request(
            'POST', '/register/v1', [], [], [],
            $content
        );

        $response = $client->getResponse();
        self::assertEquals(201, $response->getStatusCode());
    }
}