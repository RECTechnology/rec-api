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
        $id_telepay=$baseTransaction->getId();
        $id=microtime(true)*10000;

        $barcode = $this->paynetReferenceProvider->request($id,$amount,$description);

        if($barcode === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData(array(
            'receivedData'  =>  $barcode,
            'id_paynet'     =>  $id
        ));

        unset($barcode['id']);
        unset($barcode['amount']);

        $baseTransaction->setDataOut($barcode);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){
        $data=$transaction->getData();
        $client_reference=$data['id_paynet'];

        $status=$this->paynetReferenceProvider->status($client_reference);

        //$transaction->setDataOut($status);
        if($status['error_code']==0){

            switch ($status['status_code']) {
                case 0:
                    $status['status_description']='Printed';
                    $transaction->setStatus('created');
                    break;
                case 1:
                    $status['status_description']='Pending';
                    $transaction->setStatus('review');
                    break;
                case 2:
                    $status['status_description']='Authorized';
                    $transaction->setStatus('success');
                    break;
                case 3:
                    $status['status_description']='Canceled';
                    $transaction->setStatus('cancelled');
                    break;
                case 4:
                    $status['status_description']='Reversed';
                    $transaction->setStatus('review');
                    break;
                case 5:
                    $status['status_description']='Reserved';
                    $transaction->setStatus('review');
                    break;
                case 6:
                    $status['status_description']='Revision';
                    $transaction->setStatus('review');
                    break;
                default:
                    $status['status_description']='Unexpected error';
                    break;
            }

        }

        return $transaction;
    }


}