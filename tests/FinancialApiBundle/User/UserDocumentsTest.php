<?php

namespace Test\FinancialApiBundle\User;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

class UserDocumentsTest extends BaseApiTest
{
    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testGetDocuments(){

    }
    function testCreateDocument(){

        $this->rest(
            "POST",
            "/user/v4/documents",
            [
                'content' => 'https://rec.barcelona/wp-content/uploads/2018/12/RecNadal-2.jpg',
                'name' => 'doc_test',
                'kind_id' => 1
            ],
            [],
            200
        );

        $resp = $this->rest(
            "GET",
            "/user/v4/documents"
        );

        self::assertGreaterThanOrEqual(1, count($resp));
    }
}