<?php

namespace Test\FinancialApiBundle\Open;

use Faker\Factory;
use Test\FinancialApiBundle\BaseApiTest;

class RegisterTest extends BaseApiTest {

    public function testRegisterResponds201(){
        $faker = Factory::create();

        $pw = $faker->password(6);
        $pin = $faker->randomNumber(4, true);
        $dni = '38305314X'; //got from https://generadordni.es/#dni
        $content = [
            'username' => $faker->userName,
            'name' => $faker->name,
            'password' => $pw,
            'repassword' => $pw,
            'phone' => $faker->randomNumber(9, true),
            'prefix' => '34',
            'pin' => $pin,
            'repin' => $pin,
            'dni' => $dni,
            'security_question' => $faker->text(200),
            'security_answer' => $faker->text(50),
        ];
        $response = $this->request('POST', '/register/v1/commerce/mobile', $content);
        self::assertEquals(
            201,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }
}