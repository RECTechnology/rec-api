<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class UserUpdateKYCTest
 * @package Test\FinancialApiBundle\User
 */
class UserUpdateKYCTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
    }

    function testUpdateDocuments()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $resp = $this->rest(
            'POST',
            '/user/v1/save_kyc',
            [
                'document_front' => 'https://api.rec.qbitartifacts.com/static/5ec7b94fa3631.jpeg',
                'document_rear' => 'https://api.rec.qbitartifacts.com/static/5ec7b94fa3631.jpeg',
                'company' => 1

            ]
        );
        $output = $this->runCommand('rec:mailing:send');
        self::assertMatchesRegularExpression("/Processing 0 mailings/", $output);
    }

    function testUpdateKycV3()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = '/user/v3/kycs/1';
        $params = [
            "street_name" => "Barraca"
        ];
        $resp = $this->requestJson('PUT', $route, $params);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);

        $resp2 = $this->requestJson('GET', "/user/v1/account");
        $content2 = json_decode($resp2->getContent(), true);

        $data = $content2['data'];
        $kyc = $data['kyc_validations'];
        self::assertEquals($kyc['street_name'], 'Barraca');
    }

    function testUpdateDateBirthRetroCompatibilityV3ShouldWork()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = '/user/v3/kycs/1';
        //Current format
        $params = [
            "dateBirth" => "2002-01-01T00:00:00.000Z"
        ];
        $resp = $this->requestJson('PUT', $route, $params);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        //retro compatibility format
        $params = [
            "dateBirth" => "2002-01-01T00:00:00.000"
        ];
        $resp = $this->requestJson('PUT', $route, $params);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );


    }

    function testUpdateKycProtectedFieldsShouldFailV3()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = '/user/v3/kycs/1';
        $protectedFields = [
            "full_name_validated",
            "email_validated",
            "validation_phone_code",
            "phone_validated",
            "dateBirth_validated",
            "document_front_status",
            "document_rear_status",
            "document_validated",
            "country_validated",
            "address_validated",
            "proof_of_residence",
            "tier1_status",
            "tier2_status",
            "tier1_status_request",
            "tier2_status_request",
        ];
        foreach ($protectedFields as $field){
            $params = [
                $field => 1
            ];
            $resp = $this->requestJson('PUT', $route, $params);

            self::assertEquals(
                403,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

    }


    function testUpdateZip(){
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $resp = $this->rest(
            'POST',
            '/user/v1/save_kyc',
            [
                'zip' => 46500

            ]
        );

        $userInfo = $this->rest(
            'GET',
            '/user/v1/account'
        );

        self::assertEquals(46500, $userInfo->kyc_validations->zip);
    }

    function testUpdateKycWrongValues()
    {
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
        $route = '/user/v3/kycs/1';
        $params = [
            "gender" => "MF"
        ];
        $resp = $this->requestJson('PUT', $route, $params);

        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        self::assertStringContainsString(
            "Invalid value for gender, valid options: M, F, NB",
            $resp->getContent()
        );
    }

}
