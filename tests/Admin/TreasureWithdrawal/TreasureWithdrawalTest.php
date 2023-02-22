<?php

namespace App\Tests\Admin\TreasureWithdrawal;

use App\Entity\TreasureWithdrawal;
use App\Tests\Admin\AdminApiTest;

/**
 * Class TreasureWithdrawalTest
 * @package App\Tests\Admin\TreasureWithdrawal
 * @group mongo
 */
class TreasureWithdrawalTest extends AdminApiTest {

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
    function testEmailContent(){
        $templating = self::$kernel->getContainer()->get("templating");
        $message =  $templating->render(
            'Email/central_withdrawal.html.twig',
            ['link' => "http://google.com", 'name' => 'test_user', 'amount' => '55', 'day' => "31",
                'month' => "12", 'year' => "2020", 'hour' => "23",
                'minutes' => "59", 'seconds' => "58"]
        );
        self::assertStringContainsString('El usuario test_user ha solicitado una retirada de 55 Recs desde la Cuenta del Tesoro a la
                cuenta central de Novact el dia 31/12/2020 a las 23:59:58 para
                aprobar esta solicitud debes hacer click en el enlace que aparece en este email. Cuando todos los
                administradores aprueben esta soliciutd, se realizará la operación, sino, esta caducará en 24h.', $message);

    }
}