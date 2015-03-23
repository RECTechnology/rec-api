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

class UkashRedemptionService extends BaseService{

    private $ukashRedemptionProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $ukashRedemptionProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->ukashRedemptionProvider = $ukashRedemptionProvider;
    }

    public function getFields(){
        return array(
            'currency',
            'voucher_value',
            'voucher_number',
            'amount'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $currency = $baseTransaction->getDataIn()['currency'];
        $amount = round($baseTransaction->getDataIn()['amount']/100,2);
        $voucher_value = $baseTransaction->getDataIn()['voucher_value'];
        $voucher_number = $baseTransaction->getDataIn()['voucher_number'];

        $merchant_id='Telepay';

        $id=$baseTransaction->getId();

        $ukash = $this->ukashRedemptionProvider->redemption($merchant_id,$currency,$id,$voucher_value,$voucher_number,$amount);

        if($ukash === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");


        if($ukash['txCode']==99){
            throw new HttpException(400,$ukash['errDescription']);
        }

        $baseTransaction->setData($ukash);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

}