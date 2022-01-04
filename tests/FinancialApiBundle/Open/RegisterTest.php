<?php

namespace Test\FinancialApiBundle\Open;

use App\FinancialApiBundle\DataFixture\UserFixture;
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
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'pin' => $pin,
            'repin' => $pin,
            'dni' => $dni,
            'security_question' => $this->faker->text(200),
            'security_answer' => $this->faker->text(50),
        ];
        $response = $this->requestJson('POST', '/register/v1/commerce/mobile', $content);
        self::assertEquals(
            201,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }

    public function testGoodRegisterV4Responds204(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314X'; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'n9030699d',
            'company_name' => 'panaderia'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/app/v4/register', $content, $header);
        self::assertEquals(
            204,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }

    public function testRegisterV4AutonomoResponds204(){
        //Test based on thisd error
        //https://sentry.io/organizations/qbit-artifacts/issues/2540411848/?project=1517242&referrer=github_integration
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = 'X8000107V'; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'X8000107V',
            'company_name' => 'panaderia'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/app/v4/register', $content, $header);
        self::assertEquals(
            204,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }
    public function testRegisterV4WrongDNIResponds400(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314X '; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'n9030699d',
            'company_name' => 'panaderia'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/app/v4/register', $content, $header);
        self::assertEquals(
            400,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }

    public function testRegisterV4WrongCIFResponds400(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314X'; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'n9030699d ',
            'company_name' => 'panaderia'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/app/v4/register', $content, $header);
        self::assertEquals(
            400,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }
}