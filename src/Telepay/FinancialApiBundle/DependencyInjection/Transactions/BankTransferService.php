<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class BankTransferService extends BaseService{

    private $bankTransferProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $bankTransferProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->bankTransferProvider = $bankTransferProvider;
    }

    public function getFields(){
        return array(
            'amount','description'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $description = $baseTransaction->getDataIn()['description'];

        $id = $baseTransaction->getId();

        $bankTransfer = $this->bankTransferProvider->request();

        if($bankTransfer === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($bankTransfer);
        $baseTransaction->setDataOut($bankTransfer);

        return $baseTransaction;

    }

    //Regenera la tpv con los mismos datos
    public function update(Transaction $transaction, $data){

        return $transaction;
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){
        $client_reference = $transaction->getId();

        $status = $this->bankTransferProvider->status($client_reference);

        $transaction->setData($status);

        return $transaction;
    }

    public function notificate(Transaction $transaction , $request){

        return $transaction;

    }

}