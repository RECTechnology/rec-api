<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\EasyBitcoin\EasyBitcoin;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Services\Responses\CryptoResponse;
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

        $address = $this->cryptoProvider->getnewaddress();

        if($address === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $data = new CryptoResponse(
            $baseTransaction->getId(),
            3600,
            $address,
            $amount,
            $confirmations
        );

        $baseTransaction->setData($data->jsonSerialize());

        return $baseTransaction;

    }

    public function check(Transaction $transaction){

        $data = $transaction->getData();
        if($transaction->getStatus() === 'SUCCESS' || $transaction->getStatus() === 'EXPIRED')
            return $transaction;
        $address = $data['address'];
        $amount = $data['amount'];
        $minConfirmations = $data['min_confirmations'];
        $allReceived = $this->cryptoProvider->listreceivedbyaddress(0, true);
        foreach($allReceived as $account){
            if($account['address'] == $address && $account['amount'] >= $amount*1e8){
                $data['received'] = $account['amount'];
                $data['confirmations'] = $account['confirmations'];
                if($minConfirmations >= $account['min_confirmations'])
                    $transaction->setStatus("SUCCESS");
                else
                    $transaction->getStatus("RECEIVED");
            }
        }

        return $transaction;
    }
}