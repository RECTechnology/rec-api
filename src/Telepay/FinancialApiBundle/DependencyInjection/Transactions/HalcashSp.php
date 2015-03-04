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
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\HalcashServiceSp;
use Telepay\FinancialApiBundle\Document\Transaction;


class HalcashSp extends BaseService{

    private $halcashSpProvider;

    public function __construct($name, $cname, $role, $base64Image, $halcashSpProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $base64Image, $transactionContext);
        $this->halcashSpProvider = $halcashSpProvider;
    }

    public function getFields(){
        return array(
            'phone_number','phone_prefix','country','amount','reference','pin'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();

        $phone_number = $baseTransaction->getDataIn()['phone_number'];
        $phone_prefix = $baseTransaction->getDataIn()['phone_prefix'];
        $country = $baseTransaction->getDataIn()['country'];
        $amount = $baseTransaction->getDataIn()['amount'];
        $reference = $baseTransaction->getDataIn()['reference'];
        $pin = $baseTransaction->getDataIn()['pin'];
        $transaction_id=$baseTransaction->getId();

        $hal = $this->halcashSpProvider->sendV3($phone_number,$phone_prefix,$amount,$reference,$pin,$transaction_id);

        if($hal === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($hal);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

}