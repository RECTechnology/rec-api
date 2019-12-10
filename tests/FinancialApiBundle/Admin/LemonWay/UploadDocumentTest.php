<?php

namespace Test\FinancialApiBundle\Admin\LemonWay;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Financial\Driver\LemonWayDriver;
use Test\FinancialApiBundle\Admin\AdminApiTest;

/**
 * Class UploadDocumentTest
 * @package Test\FinancialApiBundle\Admin\LemonWay
 */
class UploadDocumentTest extends AdminApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }


    function createLemonDocumentKind() {
        return $this->rest(
            'POST',
            '/admin/v3/lemon_document_kinds',
            [
                'name' => "DNI",
                'description' => 'user dni',
                'lemon_doctype' => 0
            ]
        );
    }

    function testUploadLWDocument(){
        $doc = $this->createLemonDocumentKind();

        $user = $this->getSignedInUser();

        print $doc;

    }
}
