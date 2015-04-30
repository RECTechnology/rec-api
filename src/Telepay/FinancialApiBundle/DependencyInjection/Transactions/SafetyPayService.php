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
            'url_fail',
            'url_notification'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $currency = strtoupper($baseTransaction->getDataIn()['currency']);
        $baseTransaction->setCurrency($currency);
        //enviamos la transaccion con dos decimales
        $amount = round($baseTransaction->getDataIn()['amount']/100,2);
        //prepare notification urls
        $id=$baseTransaction->getId();
        //control service version for the notification
        $url_success = $baseTransaction->getDataIn()['url_success'];
        $url_fail = $baseTransaction->getDataIn()['url_fail'];

        $baseTransaction->setDebugData(array(
            'url_success'   =>  $url_success,
            'url_fail'      =>  $url_fail
        ));

        $date_time=new \DateTime();
        $date_time=date_format($date_time,'Y-m-dTH:i:s');

        $safety = $this->safetypayProvider->request($date_time,$currency,$amount,$url_success,$url_fail);

        if($safety === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        if($safety['error_number'] != 0){
            $message = '';
            switch($safety['error_number']){
                case 100100:
                    $message = 'General Error.';
                    break;
                case 100101:
                    $message = 'Service not allowed';
                    break;
                case 100102:
                    $message = 'Currency code error';
                    break;
                case 100103:
                    $message = 'Currency not allowed';
                    break;
                case 100104:
                    $message = 'Error number of digits';
                    break;
                case 100105:
                    $message = 'Amount must be > 0';
                    break;
            }
            $safety['error_description']=$message;
            $baseTransaction->setData($safety);
            throw new HttpException(400,'Transaction failed - '.$message);


        }
        $baseTransaction->setData($safety);
        $baseTransaction->setDataOut($safety);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function notificate(Transaction $transaction,$data){

        //TODO notify vrification
        /*$error_number = $data['ErrorNumber'];
        $dateTime = $data['ResponseDateTime'];
        $merchantReference = $data['MerchantReferenceNo'];
        $order = $data['OrderNo'];
        $signature = $data['signature'];*/

        //$data=$this->date_time.$this->currency.$this->amount.$this->merchant_reference.$this->lang.$this->tracking_code.$this->expiration.$this->url_success.$this->url_error.$this->signature_key;

        //$signature_telepay=hash('sha256', $data,false);

        //redirect to corersponding url
        $error = $data['error'];

        if( $error == '0' ){

            $transaction->setStatus(Transaction::$STATUS_SUCCESS);

        }else{

            $transaction->setStatus(Transaction::$STATUS_CANCELLED);

        }

        return $transaction;

    }

}