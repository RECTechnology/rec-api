<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Tier;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3ReadTestInterface;

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
        self::assertRegExp("/Processing 0 mailings/", $output);
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

}
