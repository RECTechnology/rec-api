<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseExchange;
use App\FinancialApiBundle\Document\Transaction;

class Exchange extends BaseExchange{

    public function __construct($currency_in, $currency_out, $name){
        parent::__construct($currency_in, $currency_out, $name);
    }

    public function getFields(){
        return array(
            'currency_in','currency_out'
        );
    }

    public function create(Transaction $baseTransaction = null){

        throw new HttpException(400,'Method not implemented');

    }

    public function update(Transaction $transaction, $data){

        throw new HttpException(400,'Method not implemented');
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){

        throw new HttpException(400,'Method not implemented');
    }

    public function notificate(Transaction $transaction , $request){

        throw new HttpException(400,'Method not implemented');

    }

}