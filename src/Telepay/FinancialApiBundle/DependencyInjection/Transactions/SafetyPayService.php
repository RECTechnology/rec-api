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

    public function __construct($name, $cname, $role, $base64Image, $safetypayProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $base64Image, $transactionContext);
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
        $amount = $baseTransaction->getDataIn()['amount'];
        $url_success = $baseTransaction->getDataIn()['url_success'];
        $url_fail = $baseTransaction->getDataIn()['url_fail'];

        $date_time=new \DateTime();
        $date_time=date_format($date_time,'Y-m-dTH:i:s');

        $safety = $this->safetypayProvider->request($date_time,$currency,$amount,$url_success,$url_fail);

        if($safety === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($safety);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

}