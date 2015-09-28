<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\AbancaService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class CryptocapitalService extends BaseService{

    private $cryptocapitalProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $cryptocapitalProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->cryptocapitalProvider = $cryptocapitalProvider;
    }

    public function getFields(){
        return array(
            'amount','description'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $description = $baseTransaction->getDataIn()['description'];

        $id=$baseTransaction->getId();

        try{
            $cryptocapital = $this->cryptocapitalProvider->request();
        }catch (Exception $e){
            throw new HttpException(400,$e->getMessage());
        }


        if($cryptocapital === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($cryptocapital);
        $baseTransaction->setDataOut($cryptocapital);

        return $baseTransaction;

    }

    //Regenera la tpv con los mismos datos
    public function update(Transaction $transaction, $data){

        // pillar la id
        $id = $transaction->getId();
        $datos = $transaction->getDataOut();
        $datos_in = $transaction->getDataIn();
        $important_data = $transaction->getData();
        $amount = $datos['Ds_Merchant_Amount'];
        $description = $datos_in['description'];
        $url_final = $important_data['url_final'];
        $url_ok = $datos['Ds_Merchant_UrlOK'];
        $url_ko = $datos['Ds_Merchant_UrlKO'];
        $trans_id = $important_data['transaction_id'].$important_data['contador'];
        $important_data['contador'] = $important_data['contador']+1;
        $important_data['transaction_id'] = $trans_id;
        $transaction->setData($important_data);

        $cryptocapital = $this->cryptocapitalProvider->request($amount, $trans_id, $description, $url_ok, $url_ko, $url_final);
        $transaction->setDataOut($cryptocapital);

        return $transaction;
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){
        $client_reference=$transaction->getId();

        $status=$this->cryptocapitalProvider->status($client_reference);

        $transaction->setData($status);

        return $transaction;
    }

    public function notificate(Transaction $transaction , $request){

        static $paramNames = array(
            'Ds_Amount',
            'Ds_Currency',
            'Ds_Order',
            'Ds_MerchantCode',
            'Ds_Signature',
            'Ds_Response'
        );

        $params = array();
        foreach ($paramNames as $paramName){
            if(isset( $request[$paramName] )){
                $params[] = $request[$paramName];
            }else{
                throw new HttpException(404,'Param '.$paramName.' not found ');
            }

        }

        $notification = $this->cryptocapitalProvider->notification($params);

        if($notification == 1){
            $transaction->setStatus(Transaction::$STATUS_SUCCESS);
        }else{
            $transaction->setStatus(Transaction::$STATUS_CANCELLED);
        }

        $debug = array(
            'notification'  =>  $notification,
            'request'   =>  $request,
            'params'    =>  $params
        );

        $transaction->setDebugData($debug);

        return $transaction;

    }

}