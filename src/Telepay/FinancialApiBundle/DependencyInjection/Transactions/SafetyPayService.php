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
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\SafetyPayment;
use Telepay\FinancialApiBundle\Document\Transaction;

class SafetyPayService extends BaseService{

    private $safetypayProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $safetypayProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->safetypayProvider = $safetypayProvider;
    }

    public function getFields(){
        return array(
            'currency',
            'amount',
            'url_success',
            'url_fail'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $currency = $baseTransaction->getDataIn()['currency'];
        $baseTransaction->setCurrency($currency);
        //enviamos la transaccion con dos decimales
        $amount = round($baseTransaction->getDataIn()['amount']/100,2);
        //prepare notification urls
        $id=$baseTransaction->getId();

        $request=$this->getTransactionContext()->getRequestStack()->getCurrentRequest();
        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_success=$url_base.'/notifications/v1/safetypay?tid='.$id.'&error=0';
        $url_fail = $url_base.'/notifications/v1/safetypay?tid='.$id.'&error=1';

        $baseTransaction->setDebugData(array(
            'url_success'   =>  $url_success,
            'url_fail'      =>  $url_fail
        ));

        $date_time=new \DateTime();
        $date_time=date_format($date_time,'Y-m-dTH:i:s');

        $safety = $this->safetypayProvider->request($date_time,$currency,$amount,$url_success,$url_fail);

        if($safety === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($safety);
        $baseTransaction->setDataOut($safety);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

}