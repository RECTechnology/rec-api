<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use App\FinancialApiBundle\Document\Transaction;

class SepaInService extends BaseService{

    private $sepaInProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $sepaInProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->sepaInProvider = $sepaInProvider;
    }

    public function getFields(){
        return array(
            'amount'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();

        $sepaIn = $this->sepaInProvider->request();

        if($sepaIn === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($sepaIn);
        $baseTransaction->setDataOut($sepaIn);

        return $baseTransaction;

    }

    //Regenera la tpv con los mismos datos
    public function update(Transaction $transaction, $data){

        throw new HttpException(400,'Method not implemented');
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){

        return $transaction;
    }

    public function notificate(Transaction $transaction , $request){

        return $transaction;

    }

    public function getInfo(){
        $response = $this->sepaInProvider->getInfo();

        return $response;
    }

}