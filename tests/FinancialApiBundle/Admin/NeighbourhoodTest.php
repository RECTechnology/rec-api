<?php

namespace Test\FinancialApiBundle\Admin;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3TestInterface;

/**
 * Class NeighbourhoodTest
 * @package Test\FinancialApiBundle\Admin
 */
class NeighbourhoodTest extends BaseApiTest implements CrudV3TestInterface {

    function setUp(): void
    {
        parent::setUp();
        $this->logIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testIndex()
    {
        $resp = $this->requestJson('GET', '/admin/v3/neighbourhoods');
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
    }

    function testExport()
    {
        $resp = $this->request('GET', '/admin/v3/neighbourhoods/export');
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
    }

    function testSearch()
    {
        $resp = $this->request('GET', '/admin/v3/neighbourhoods/search');
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
    }

    function testShow()
    {
        $resp = $this->request('GET', '/admin/v3/neighbourhoods/1');
        self::assertEquals(
            404,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
    }

    function testCreate()
    {
        $resp = $this->request(
            'POST',
            '/admin/v3/neighbourhoods',
            ['name' => 'test neighbourhood']
        );
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
    }

    function testUpdate()
    {
        // TODO: Implement testUpdate() method.
    }

    function testDelete()
    {
        // TODO: Implement testDelete() method.
    }
}
