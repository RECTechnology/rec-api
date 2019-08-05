<?php

namespace Test\FinancialApiBundle\Open;

use Faker\Factory;
use Test\FinancialApiBundle\BaseApiTest;

class RegisterTest extends BaseApiTest {

    public function testGoodRegisterResponds201(){

        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314X'; //got from https://generadordni.es/#dni
        $content = [
            'username' => $this->faker->userName,
            'name' => $this->faker->name,
            'password' => $pw,
            'repassword' => $pw,
            'phone' => $this->faker->randomNumber(9, true),
            'prefix' => '34',
            'pin' => $pin,
            'repin' => $pin,
            'dni' => $dni,
            'security_question' => $this->faker->text(200),
            'security_answer' => $this->faker->text(50),
        ];
        $response = $this->request('POST', '/register/v1/commerce/mobile', $content);
        self::assertEquals(
            201,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }
}