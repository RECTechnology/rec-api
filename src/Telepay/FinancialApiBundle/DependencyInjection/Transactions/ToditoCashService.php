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
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\ToditoCash;
use Telepay\FinancialApiBundle\Document\Transaction;

class ToditoCashService extends BaseService{

    private $tcProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $tcProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->tcProvider = $tcProvider;
    }

    public function getFields(){
        return array(
            'card_number',
            'nip',
            'amount',
            'concept',
            'currency'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $card_number = $baseTransaction->getDataIn()['card_number'];
        $nip = $baseTransaction->getDataIn()['nip'];
        $amount = $baseTransaction->getDataIn()['amount'];
        $concept = $baseTransaction->getDataIn()['concept'];
        $currency = $baseTransaction->getDataIn()['currency'];

        $id=$baseTransaction->getId();
        $date_time=new \DateTime();
        $date=date_format($date_time,'Y-m-d');
        $hour=date_format($date_time,'H:i:s');

        $tc = $this->tcProvider->request($id,$date,$hour,$card_number,$nip,$amount,$concept,$currency);

        if($tc === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($tc);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }
}