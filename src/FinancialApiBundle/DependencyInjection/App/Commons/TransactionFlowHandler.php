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

class TransactionFlowHandler{

    private $mongo;

    private $doctrine;

    private $balanceManipulator;

    public function __construct($mongo, $doctrine, $balanceManipulator){
        $this->mongo = $mongo;
        $this->doctrine = $doctrine;
        $this->balanceManipulator = $balanceManipulator;
    }

    public function sendRecsWithIntermediary(Group $rootAccount, Group $intermediaryAccount, Group $userAccount, $amount, $concept = 'Internal exchange'){
        //TODO we need to know if it's a bonification
        //send money from root to intermediary
        //rec out root
        $rootTxOut = $this->sendRecsToAddress($rootAccount, $intermediaryAccount, $amount);

        //rec in intermediary
        $intermediaryTxIn = $this->receiveRecs($intermediaryAccount, $rootTxOut);
        //rec out intermediary
        $intermediaryTxOut = $this->sendRecsToAddress($intermediaryAccount, $userAccount, $amount);
        //rec in final user
        $userAccountTxIn = $this->receiveRecs($userAccount, $intermediaryTxOut, false, $concept);

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

    }

    public function receiveRecsFromOutTx(Group $receiver, Transaction $outTx){
        $inTx = $this->receiveRecs($receiver, $outTx, false);
        $dm = $this->mongo->getManager();
        $dm->persist($inTx);
        $dm->flush();
        $this->addBalance($receiver, $inTx->getAmount(), $inTx);

    }

    private function addBalance(Group $group, $amount, Transaction $transaction, $log = 'null'){

        $this->balanceManipulator->addBalance($group, $amount, $transaction);
        $this->addToWallet($group, $amount);

    }

    private function addToWallet(Group $group, $amount){
        /** @var UserWallet $wallet */
        $wallet = $group->getWallet(Currency::$REC);
        $wallet->setAvailable($wallet->getAvailable() + $amount);
        $wallet->setBalance($wallet->getBalance() + $amount);
        $this->doctrine->getManager()->flush();
    }

    private function sendRecsToAddress(Group $from, Group $to, $amount){

        $txOut = Transaction::createInternalTransactionV3($from, 'rec', 'out', Currency::$REC);

        $dataIn = [
            'amount' => $amount,
            'concept' => 'Internal exchange',
            'url_notification' => ''
        ];
        $txOut->setDataIn($dataIn);
        $txOut->setAmount($amount);
        $txOut->setTotal($amount*-1);
        $txOut->setScale(8);
        $txOut->setInternal(true);

        $rootOutTxId = substr(Random::generateToken(), 0, 48);
        $payOutInfo = [
            'amount' => $amount,
            'address' => $to->getRecAddress(),
            'txid' => $rootOutTxId,
            'status' => Transaction::$STATUS_SENT,
            'final' => true,
            'receiver' => $to->getId(),
            'name_receiver' => $to->getName(),
            'image_receiver' => $to->getCompanyImage()
        ];

        $txOut->setPayOutInfo($payOutInfo);
        return $txOut;
    }

    private function receiveRecs(Group $receiver, Transaction $previousTx, $internal = true, $concept = 'Internal exchange'){

        $txIn = Transaction::createInternalTransactionV3($receiver, 'rec', 'in', Currency::$REC);

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
            'sender' => $senderInfo['id_sender']
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

}