<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use App\FinancialApiBundle\Document\Transaction;

class SepaOutService extends BaseService{

    private $sepaOutProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $sepaOutProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->sepaOutProvider = $sepaOutProvider;
    }

    public function getFields(){
        return array(
            'beneficiary', 'iban', 'amount', 'bic_swift'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        //TODO check correct iban
        $iban = $baseTransaction->getDataIn()['iban'];
        $beneficiary = $baseTransaction->getDataIn()['beneficiary'];
        $concept = $baseTransaction->getDataIn()['description'];
        //TODO check correct swift_bic
        $swift_bic = $baseTransaction->getDataIn()['swift_bic'];

        $sepaOut = $this->sepaOutProvider->request($amount, $iban, $swift_bic, $beneficiary, $concept);

        if($sepaOut === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($sepaOut);
        $baseTransaction->setDataOut($sepaOut);

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

        throw new HttpException(400,'Method not implemented');

    }

}