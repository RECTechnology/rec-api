<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\IntegerManipulator;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class CryptoSendService extends BaseService {

    private $cryptoProvider;
    private $minimum_amount;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $cryptoProvider, $transactionContext,$minimum_amount){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->cryptoProvider = $cryptoProvider;
        $this->minimum_amount = $minimum_amount;
    }

    public function getFields(){
        return array(
            'amount', 'address'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $address = $baseTransaction->getDataIn()['address'];

        $im = new IntegerManipulator();
        if(!$im->isInteger($amount)) throw new HttpException(400, "amount must be an integer (".$amount.") given");
        if($amount <= 0) throw new HttpException(400, "Amount must be positive");

        if($amount < $this->minimum_amount) throw new HttpException(400,"Minimum amount not reached");

        //verify crypto address
        $address_verification = $this->cryptoProvider->validateaddress($address);

        if(!$address_verification['isvalid']) throw new HttpException(400,'Invalid address.');

        $crypto = $this->cryptoProvider->sendtoaddress($address, $amount/1e8);

        if($crypto === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData(array(
            'id' => $baseTransaction->getId(),
            'address' => $address,
            'amount' => doubleval($amount),
            'txid' => $crypto->txid
        ));

        if($crypto){
            $baseTransaction->setStatus('success');
            $response = $crypto;
        }else{
            $baseTransaction->setStatus('failed');
            $response = $crypto;
        }
        $baseTransaction->setDataOut(array(
            'txid' => $response
        ));

        return $baseTransaction;

    }
    private function hasExpired($transaction){
        return $transaction->getTimeIn()->getTimestamp()+$transaction->getData()['expires_in'] < time();
    }

    public function check(Transaction $transaction){

        $currentData = $transaction->getData();

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction))
            $transaction->setStatus('expired');

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired')
            return $transaction;

        $address = $currentData['address'];
        $amount = $currentData['amount'];
        $allReceived = $this->cryptoProvider->listreceivedbyaddress(0, true);
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] == $address && doubleval($cryptoData['amount'])*1e8 >= $amount){
                $currentData['received'] = doubleval($cryptoData['amount'])*1e8;
                $currentData['confirmations'] = $cryptoData['confirmations'];
                if($currentData['confirmations'] >= $currentData['min_confirmations'])
                    $transaction->setStatus("success");
                else
                    $transaction->setStatus("received");
                $transaction->setData($currentData);
                $transaction->setDataOut($currentData);
                return $transaction;
            }
        }
        return $transaction;
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }
}