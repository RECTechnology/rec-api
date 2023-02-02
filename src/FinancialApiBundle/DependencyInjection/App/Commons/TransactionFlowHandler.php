<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Balance;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;
use FOS\OAuthServerBundle\Util\Random;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransactionFlowHandler{

    private $mongo;

    private $doctrine;

    private $balanceManipulator;

    private $container;

    public function __construct($mongo, $doctrine, $balanceManipulator, ContainerInterface $container){
        $this->mongo = $mongo;
        $this->doctrine = $doctrine;
        $this->balanceManipulator = $balanceManipulator;
        $this->container = $container;
    }

    public function sendRecsWithIntermediary(Group $rootAccount, Group $intermediaryAccount, Group $userAccount, $amount, $concept = 'Internal exchange', $isBonification = false, $bonification_campaign_id = null): Transaction
    {
        $this->checkBalance($rootAccount, $amount);
        //TODO we need to know if it's a bonification
        //send money from root to intermediary
        //rec out root
        $rootTxOut = $this->sendRecsToAddress($rootAccount, $intermediaryAccount, $amount, false);

        //rec in intermediary
        $intermediaryTxIn = $this->receiveRecs($intermediaryAccount, $rootTxOut, true, 'Internal exchange', false, null);
        //rec out intermediary
        $intermediaryTxOut = $this->sendRecsToAddress($intermediaryAccount, $userAccount, $amount);
        //rec in final user
        $userAccountTxIn = $this->receiveRecs($userAccount, $intermediaryTxOut, false, $concept, $isBonification, $bonification_campaign_id);

        $dm = $this->mongo->getManager();
        $dm->persist($rootTxOut);
        $dm->persist($intermediaryTxIn);
        $dm->persist($intermediaryTxOut);
        $dm->persist($userAccountTxIn);
        $dm->flush();

        $this->addBalance($rootAccount, $amount*-1, $rootTxOut);
        $this->addBalance($intermediaryAccount, $amount, $intermediaryTxIn);
        $this->addBalance($intermediaryAccount, $amount*-1, $intermediaryTxOut);
        $this->addBalance($userAccount, $amount, $userAccountTxIn);

        return $userAccountTxIn;
    }

    public function receiveRecsFromOutTx(Group $receiver, Transaction $outTx): Transaction
    {
        $payOutInfo = $outTx->getPayOutInfo();
        $inTx = $this->receiveRecs($receiver, $outTx, false, $payOutInfo['concept'], false, null);
        $dm = $this->mongo->getManager();
        $dm->persist($inTx);
        $dm->flush();
        $this->addBalance($receiver, $inTx->getAmount(), $inTx);
        return $inTx;

    }

    private function addBalance(Group $group, $amount, Transaction $transaction, $log = 'null'): void
    {

        $this->balanceManipulator->addBalance($group, $amount, $transaction);
        $this->addToWallet($group, $amount);

    }

    private function addToWallet(Group $group, $amount): void
    {
        /** @var UserWallet $wallet */
        $wallet = $group->getWallet($this->getCryptoCurrency());
        $wallet->setAvailable($wallet->getAvailable() + $amount);
        $wallet->setBalance($wallet->getBalance() + $amount);
        $this->doctrine->getManager()->flush();
    }

    private function sendRecsToAddress(Group $from, Group $to, $amount, $internal = true): Transaction
    {

        $txOut = Transaction::createInternalTransactionV3($from, strtolower($this->getCryptoCurrency()), 'out', $this->getCryptoCurrency());

        $dataIn = [
            'amount' => $amount,
            'concept' => 'Internal exchange',
            'url_notification' => ''
        ];
        $txOut->setDataIn($dataIn);
        $txOut->setAmount($amount);
        $txOut->setTotal($amount*-1);
        $txOut->setScale(8);
        $txOut->setInternal($internal);

        $rootOutTxId = substr(Random::generateToken(), 0, 48);
        $payOutInfo = [
            'amount' => $amount,
            'address' => $to->getRecAddress(),
            'concept' => 'Internal exchange',
            'txid' => $rootOutTxId,
            'status' => Transaction::$STATUS_SENT,
            'final' => true,
            'receiver' => $to->getId(),
            'name_receiver' => $to->getName(),
            'image_receiver' => $to->getCompanyImage(),
            'receiver_id' => $to->getId()
        ];

        $txOut->setPayOutInfo($payOutInfo);
        return $txOut;
    }

    private function receiveRecs(Group $receiver, Transaction $previousTx, $internal = true, $concept = 'Internal exchange', $isBonification = false, $bonification_campaign_id): Transaction
    {

        $txIn = Transaction::createInternalTransactionV3($receiver, strtolower($this->getCryptoCurrency()), 'in', $this->getCryptoCurrency());

        //TODO fix concept dependding if it's internal or not
        $txDataIn = $previousTx->getDataIn();
        $dataIn = [
            'amount' => $previousTx->getAmount(),
            'concept' => $concept,
            'url_notification' => ''
        ];
        $txIn->setDataIn($dataIn);

        $txIn->setAmount($previousTx->getAmount());
        $txIn->setTotal($previousTx->getAmount());
        $txIn->setScale(8);
        $txIn->setInternal($internal);
        $txIn->setPaymentOrderId($previousTx->getPaymentOrderId());
        $txIn->setIsBonification($isBonification);
        $txIn->setBonificationCampaignId($bonification_campaign_id);

        $senderInfo = $this->getSenderInfo($previousTx);

        $paymentInfo = $previousTx->getPayOutInfo();
        $payInInfo = [
            'amount' => $previousTx->getAmount(),
            'currency' => $previousTx->getCurrency(),
            'address' => $receiver->getRecAddress(),
            'txid' => $paymentInfo['txid'],
            'received' => $previousTx->getAmount(),
            'expires_in' => 1200,
            'min_confirmations' => 1,
            'confirmations' => 4,
            'concept' => $concept,
            'status' => Transaction::$STATUS_SUCCESS,
            'final' => true,
            'image_sender' => $senderInfo['image_sender'],
            'name_sender' => $senderInfo['name_sender'],
            'sender_id' => $senderInfo['id_sender']
        ];

        $txIn->setPayInInfo($payInInfo);

        return $txIn;
    }

    private function getSenderInfo(Transaction $previousTx): array
    {
        $sender_id = $previousTx->getGroup();
        /** @var Group $sender */
        $sender = $this->doctrine->getRepository(Group::class)->find($sender_id);

        if($previousTx->getInternal()){
            $senderInfo = array(
                'image_sender' => '',
                'name_sender' => "Treasure account",
                'id_sender' => 0
            );
        }else{
            $senderInfo = array(
                'image_sender' => $sender->getCompanyImage(),
                'name_sender' => $sender->getName(),
                'id_sender' => $sender->getId()
            );
        }


        return $senderInfo;

    }

    private function checkBalance(Group $account, $amount){
        /** @var UserWallet $recWallet */
        $recWallet = $account->getWallet($this->getCryptoCurrency());
        if($recWallet->getAvailable() < $amount){
            throw new HttpException(403, 'Not funds enough');
        }
    }

    private function getCryptoCurrency(){
        return $this->container->getParameter("crypto_currency");
    }

}