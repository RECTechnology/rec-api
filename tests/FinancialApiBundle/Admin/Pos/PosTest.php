<?php

namespace Test\FinancialApiBundle\Admin\Pos;

use Test\FinancialApiBundle\Admin\AdminApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class PosTest
 * @package Test\FinancialApiBundle\Admin\Pos
 */
class PosTest extends AdminApiTest implements CrudV3WriteTestInterface {

    const ROUTE = "/admin/v3/pos";

    function testCreate()
    {
        $account = $this->getOneAccount();
        $this->createPos($account);
    }

    function testUpdate()
    {
        $account = $this->getOneAccount();
        $pos = $this->createPos($account);
        $this->updatePos($pos, ['active' => true]);
        $this->updatePos($pos, ['notification_url' => "https://rec.barcelona"]);
    }

    function testDelete()
    {
        $account = $this->getOneAccount();
        $pos = $this->createPos($account);
        $this->deletePos($pos);
    }

    private function getOneAccount()
    {
        $route = "/admin/v3/accounts";
        return $this->rest('GET', $route)[0];
    }

    private function createPos($account)
    {
        return $this->rest('POST', self::ROUTE, [
            'account_id' => $account->id,
            'notification_url' => "https://rec.barcelona"
        ]);
    }

    private function updatePos($pos, array $params)
    {
        return $this->rest('PUT', self::ROUTE . "/{$pos->id}", $params);
    }

    private function deletePos($pos)
    {
        return $this->rest('DELETE', self::ROUTE . "/{$pos->id}");
    }
}
