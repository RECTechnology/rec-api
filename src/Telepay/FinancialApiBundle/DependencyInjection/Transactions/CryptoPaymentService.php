<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class CryptoPaymentService extends BaseService {

    private $cryptoProvider;

    public function __construct($name, $cname, $role, $base64Image, $cryptoProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $base64Image, $transactionContext);
        $this->cryptoProvider = $cryptoProvider;
    }

    public function getFields(){
        return array(
            'amount', 'confirmations'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $confirmations = $baseTransaction->getDataIn()['confirmations'];

        if($amount <= 0 ) throw new HttpException(400, "Amount must be positive");
        if($confirmations < 0 ) throw new HttpException(400, "Confirmation number can't be negative");

        $address = $this->cryptoProvider->getnewaddress();

        if($address === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData(array(
            'id' => $baseTransaction->getId(),
            'address' => $address,
            'expires_in' => 3600,
            'amount' => intval($amount),
            'received' => 0.0,
            'min_confirmations' => intval($confirmations),
            'confirmations' => 0,
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
            if($cryptoData['address'] == $address && floatval($cryptoData['amount'])*1e8 >= $amount){
                $currentData['received'] = floatval($cryptoData['amount'])*1e8;
                $currentData['confirmations'] = $cryptoData['confirmations'];
                if($currentData['confirmations'] >= $currentData['min_confirmations'])
                    $transaction->setStatus("success");
                else
                    $transaction->setStatus("received");
                $transaction->setData($currentData);
                return $transaction;
            }
        }
        return $transaction;
    }
}