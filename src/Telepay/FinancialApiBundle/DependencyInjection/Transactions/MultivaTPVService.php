<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\MultivaService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;


class MultivaTPVService extends BaseService{

    private $paynetReferenceProvider;

    public function __construct($name, $cname, $role, $base64Image, $halcashSpProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $base64Image, $transactionContext);
        $this->paynetReferenceProvider = $halcashSpProvider;
    }

    public function getFields(){
        return array(
            'amount','url_notification'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];

        $id=$baseTransaction->getId();

        $request=$this->getTransactionContext()->getRequestStack()->getCurrentRequest();
        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_final='/notifications/v1/multiva/'.$id;

        $tpv = $this->paynetReferenceProvider->request($amount, $id, $url_base,$url_final);

        if($tpv === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($tpv);

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