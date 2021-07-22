<?php

namespace Test\FinancialApiBundle\Admin\Documents;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class DocumentsTest
 * @package Test\FinancialApiBundle\Admin\Documents
 */
class DocumentsTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testGetDocumentsFromAdminShouldWork()
    {
        $route = "/admin/v3/documents";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testGetDocumentsFromAdminFilteringByFieldShouldReturnOnlyFilteredDocuments()
    {
        $route = "/admin/v3/documents?account_id=1";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];
        foreach ($elements as $element){
            self::assertEquals(1, $element['account']['id']);
        }

        $route = "/admin/v3/documents?status=rec_submitted";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];
        foreach ($elements as $element){
            self::assertEquals('rec_submitted', $element['status']);
        }

        $route = "/admin/v3/documents?user_id=1";
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];
        foreach ($elements as $element){
            self::assertEquals(1, $element['user_id']);
        }


    }
}
