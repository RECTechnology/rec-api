<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\UkashBarcode;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class UkashGenerateService extends BaseService{

    private $ukashGenerateProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $ukashGenerateProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->ukashGenerateProvider = $ukashGenerateProvider;
    }

    public function getFields(){
        return array(
            'currency','amount'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $currency = $baseTransaction->getDataIn()['currency'];
        $amount = round($baseTransaction->getDataIn()['amount']/100,2);
        $merchant_id='Telepay';

        $id=$baseTransaction->getId();

        $barcode = $this->ukashGenerateProvider->request($merchant_id,$currency,$id,$amount);

        if($barcode === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        if($barcode['txCode']==99){
            throw new HttpException(400,$barcode['errDescription']);
        }
        $baseTransaction->setData($barcode);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

}