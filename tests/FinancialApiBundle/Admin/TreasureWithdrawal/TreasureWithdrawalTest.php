<?php

namespace Test\FinancialApiBundle\Admin\TreasureWithdrawal;

use Test\FinancialApiBundle\Admin\AdminApiTest;

/**
 * Class TreasureWithdrawalTest
 * @package Test\FinancialApiBundle\Admin\TreasureWithdrawal
 */
class TreasureWithdrawalTest extends AdminApiTest {


    function createWithdrawal(){
        return $this->rest(
            'POST',
            '/admin/v3/treasure_withdrawals',
            [
                'amount' => 1e8,
                'description' => "Generamos 1 REC"
            ]
        );
    }

    function testFullProcess(){
        $withdrawal = $this->createWithdrawal();
    }
}