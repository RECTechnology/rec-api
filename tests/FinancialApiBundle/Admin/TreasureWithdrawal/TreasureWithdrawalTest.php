<?php

namespace Test\FinancialApiBundle\Admin\TreasureWithdrawal;

use App\FinancialApiBundle\Entity\TreasureWithdrawal;
use Test\FinancialApiBundle\Admin\AdminApiTest;
use Test\FinancialApiBundle\Utils\MongoDBTrait;

/**
 * Class TreasureWithdrawalTest
 * @package Test\FinancialApiBundle\Admin\TreasureWithdrawal
 */
class TreasureWithdrawalTest extends AdminApiTest {

    use MongoDBTrait;

    function testFullProcess(){

        $amount = 34 * 1e8;

        $rootBefore = $this->getRootAccount();

        $withdrawal = $this->createWithdrawal($amount);
        self::assertEquals(TreasureWithdrawal::STATUS_PENDING, $withdrawal->status);
        self::assertGreaterThan(1, count($withdrawal->validations));
        for($i=1; $i<count($withdrawal->validations); $i++){
            $val = $withdrawal->validations[$i];
            $this->validateEmail($val, $val->token);
            $withdrawal = $this->fetchWithdrawal($withdrawal);
            self::assertEquals(TreasureWithdrawal::STATUS_PENDING, $withdrawal->status);
        }
        $this->validateEmail($withdrawal->validations[0], 'this_token_is_invalid', 400);
        $withdrawal = $this->fetchWithdrawal($withdrawal);
        self::assertEquals(TreasureWithdrawal::STATUS_PENDING, $withdrawal->status);


        $this->validateEmail($withdrawal->validations[0], $withdrawal->validations[0]->token);
        $withdrawal = $this->fetchWithdrawal($withdrawal);
        self::assertEquals(TreasureWithdrawal::STATUS_APPROVED, $withdrawal->status);

        $this->runCommand('rec:crypto:check');
        $rootAfter = $this->getRootAccount();

        self::assertEquals($rootBefore->wallets[0]->available + $amount, $rootAfter->wallets[0]->available);
    }

    private function fetchWithdrawal($withdrawal){
        return $this->rest(
            'GET',
            "/admin/v3/treasure_withdrawals/{$withdrawal->id}"
        );
    }

    /**
     * @param float $amount
     * @return array|\stdClass
     */
    private function createWithdrawal($amount = 1e8) {
        return $this->rest(
            'POST',
            '/admin/v3/treasure_withdrawals',
            [
                'amount' => $amount,
                'description' => "Generamos 1 REC"
            ]
        );
    }

    /**
     * @param $validation
     * @param $token
     * @param int $expectedStatusCode
     */
    private function validateEmail($validation, $token, $expectedStatusCode=200) {
        $this->rest(
            'PUT',
            "/public/v3/treasure_withdrawal_validations/{$validation->id}",
            ['token' => $token],
            [],
            $expectedStatusCode
        );
    }

    private function getRootAccount() {
        $rootId = self::createClient()->getContainer()->getParameter('id_group_root');
        return $this->rest('GET', "/admin/v3/accounts/{$rootId}");
    }
}