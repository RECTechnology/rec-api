<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\Transactions\Libs\MultivaService;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use App\FinancialApiBundle\Document\Transaction;


class MultivaTPVService extends BaseService{

    private $multivaProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $multivaProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->multivaProvider = $multivaProvider;
    }

    public function getFields(){
        return array(
            'amount','url_notification'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];

        $id=$baseTransaction->getId();

        $url_final='/notifications/v1/multiva/'.$id;

        $tpv = $this->multivaProvider->request($amount, $id,$url_final);

        if($tpv === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($tpv);
        $baseTransaction->setDataOut($tpv);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function notificate(Transaction $transaction,$data){

        return $transaction;

    }


}