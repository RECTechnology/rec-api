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
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\Halcash;
use Telepay\FinancialApiBundle\Document\Transaction;


class SpeiService extends BaseService{

    private $speiProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $speiProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->speiProvider = $speiProvider;
    }

    public function getFields(){
        return array(
            'name', 'reference', 'amount'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();

        $reference = $baseTransaction->getDataIn()['reference'];
        $name = $baseTransaction->getDataIn()['name'];
        $amount = $baseTransaction->getDataIn()['amount'];

        //pasamos a euros porque lo recibimos en centimos
        $amount = $amount/100;

        if(strlen($reference) > 210) throw new HttpException(400,'Reference Field must be less than 210 characters');

        $transaction_id = $baseTransaction->getId();

        $stp = $this->speiProvider->register($name,$reference,$amount,$transaction_id);

        if($stp === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($stp);
        $baseTransaction->setDataOut($stp);

        //todo control de errores


        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

        return $transaction;

    }

    public function cancel(Transaction $transaction,$data){

        return $transaction;

    }

    public function check(Transaction $transaction){


        return $transaction;

    }

}