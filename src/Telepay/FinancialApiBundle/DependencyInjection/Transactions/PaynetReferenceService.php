<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\PaynetGetBarcode;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\PaynetGetStatus;
use Telepay\FinancialApiBundle\Document\Transaction;


class PaynetReferenceService extends BaseService{


    private $paynetReferenceProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $paynetReferenceProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->paynetReferenceProvider = $paynetReferenceProvider;
    }

    public function getFields(){
        return array(
            'amount','description'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        //lo dividimos por 100 porque lo recibimos en centimos i lo tenemos que enviar en euros
        $amount = $baseTransaction->getDataIn()['amount']/100;
        $description = $baseTransaction->getDataIn()['description'];
        $id=$baseTransaction->getId();

        $barcode = $this->paynetReferenceProvider->request($id,$amount,$description);

        if($barcode === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($barcode);
        $baseTransaction->setStatus('pending');

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function check(Transaction $transaction){
        $client_reference=$transaction->getId();

        $status=$this->paynetReferenceProvider->status($client_reference);

        $transaction->setData($status);

        return $transaction;
    }


}