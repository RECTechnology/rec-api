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
