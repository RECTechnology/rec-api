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
        $resp = $this->request('GET', '/admin/v3/neighbourhoods');
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
    }

    function testExport()
    {
        // TODO: Implement testExport() method.
    }

    function testSearch()
    {
        // TODO: Implement testSearch() method.
    }

    function testShow()
    {
        // TODO: Implement testShow() method.
    }

    function testCreate()
    {
        // TODO: Implement testCreate() method.
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
