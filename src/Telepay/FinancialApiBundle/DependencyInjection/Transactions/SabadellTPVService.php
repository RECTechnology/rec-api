<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\SabadellService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class SabadellTPVService extends BaseService{

    private $sabadellProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $sabadellProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->sabadellProvider = $sabadellProvider;
    }

    public function getFields(){
        return array(
            'amount','description','url_notification','url_ok','url_ko'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $description = $baseTransaction->getDataIn()['description'];
        $url_ok = $baseTransaction->getDataIn()['url_ok'];
        $url_ko = $baseTransaction->getDataIn()['url_ko'];
        $id=$baseTransaction->getId();
        $request=$this->getTransactionContext()->getRequestStack()->getCurrentRequest();
        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_final='/notifications/v2/sabadell/'.$id;

        $sabadell = $this->sabadellProvider->request($amount,$id,$description,$url_base,$url_ok,$url_ko,$url_final);

        if($sabadell === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $timestamp=new \DateTime();
        $timestamp=$timestamp->getTimestamp();
        $trans_id=$timestamp;

        $important_data=array(
            'url_base'  =>  $url_base,
            'url_final' =>  $url_final,
            'contador'  =>  0,
            'transaction_id'    =>  $trans_id
        );

        $baseTransaction->setData($important_data);
        $sabadell['id_telepay']=$id;
        $baseTransaction->setDataOut($sabadell);

        return $baseTransaction;

    }

    //Regenera la tpv con los mismos datos
    public function update(Transaction $transaction, $data){

        // pillar la id
        $id=$transaction->getId();
        $datos=$transaction->getDataOut();
        $datos_in=$transaction->getDataIn();
        $important_data=$transaction->getData();
        $amount=$datos['Ds_Merchant_Amount'];
        $description=$datos_in['description'];
        $url_base=$important_data['url_base'];
        $url_final=$important_data['url_final'];
        $url_ok=$datos['Ds_Merchant_UrlOK'];
        $url_ko=$datos['Ds_Merchant_UrlKO'];
        $trans_id=$important_data['transaction_id'].$important_data['contador'];
        $important_data['contador']=$important_data['contador']+1;
        $important_data['transaction_id']= $trans_id;
        $transaction->setData($important_data);

        $sabadell = $this->sabadellProvider->request($amount,$trans_id,$description,$url_base,$url_ok,$url_ko,$url_final);
        $transaction->setDataOut($sabadell);

        return $transaction;
    }

    public function check(Transaction $transaction){
        $client_reference=$transaction->getId();

        $status=$this->sabadellProvider->status($client_reference);

        $transaction->setData($status);

        return $transaction;
    }


}