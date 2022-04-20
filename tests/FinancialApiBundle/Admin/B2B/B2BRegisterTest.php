<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class MailingTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class B2BRegisterTest extends BaseApiTest {

    public function testGoodRegisterV4Responds204(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314x'; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'n9030699d',
            'company_name' => 'panaderia',
            'platform' => 'rezero-b2b-web',
            'rezero_b2b_username' => 'B2B_user_name'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/rezero_b2b/v4/register', $content, $header);
        self::assertEquals(
            204,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );

        $duplicatedContent = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => '58720506B',
            'company_cif' => 'H68168178',
            'company_name' => 'panaderia2',
            'platform' => 'rezero-b2b-web',
            'rezero_b2b_username' => 'B2B_user_name'
        ];
        $response = $this->requestJson('POST', '/rezero_b2b/v4/register', $duplicatedContent, $header);
        self::assertEquals(
            400,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }

    public function testRegisterV4WrongPlatformResponds403(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314x'; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'n9030699d',
            'company_name' => 'panaderia',
            'platform' => 'wrong',
            'rezero_b2b_username' => 'B2B_user_name'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/rezero_b2b/v4/register', $content, $header);
        self::assertEquals(
            403,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }

    public function testRegisterV4WrongUsernameResponds403(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314x'; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'n9030699d',
            'company_name' => 'panaderia',
            'platform' => 'rezero-b2b-web',
            'rezero_b2b_username' => 'B2B user name'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/rezero_b2b/v4/register', $content, $header);
        self::assertEquals(
            403,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }

    public function testRegisterV4LargeUsernameResponds403(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $pw = $this->faker->password(6);
        $pin = $this->faker->randomNumber(4, true);
        $dni = '38305314x'; //got from https://generadordni.es/#dni
        $content = [
            'password' => $pw,
            'phone' => random_int(600000000, 799999999),
            'prefix' => '34',
            'dni' => $dni,
            'company_cif' => 'n9030699d',
            'company_name' => 'panaderia',
            'platform' => 'rezero-b2b-web',
            'rezero_b2b_username' => 'B2B_user_name_B2B_user_name_B2B_user_name_B2B_user_name_B2B_user_name_B2B_user_name'
        ];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ];
        $response = $this->requestJson('POST', '/rezero_b2b/v4/register', $content, $header);
        self::assertEquals(
            403,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }
}
