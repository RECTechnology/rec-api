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
        $name = 'test neighbourhood';
        $resp = $this->createNeighbourhood($name);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
        self::assertEquals($name, json_decode($resp->getContent())->data->name);
    }

    function createNeighbourhood($name){
        return $this->requestJson(
            'POST',
            '/admin/v3/neighbourhoods',
            ['name' => $name]
        );
    }

    function testUpdate()
    {
        $resp = $this->createNeighbourhood("initial name");
        $nhId = json_decode($resp->getContent())->data->id;

        $name = "changed name";
        $resp = $this->requestJson(
            'PUT',
            '/admin/v3/neighbourhoods/' . $nhId,
            ['name' => $name]
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
        self::assertEquals($name, json_decode($resp->getContent())->data->name);
    }

    function testDelete()
    {
        $resp = $this->createNeighbourhood("test name");
        $nhId = json_decode($resp->getContent())->data->id;

        $resp = $this->request(
            'DELETE',
            '/admin/v3/neighbourhoods/' . $nhId
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
    }
}
