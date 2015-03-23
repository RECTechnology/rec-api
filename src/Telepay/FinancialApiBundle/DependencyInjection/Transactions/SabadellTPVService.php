<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\SabadellService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class SabadellTPVService extends BaseService{

    private $sabadellProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $sabadellProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->sabadellProvider = $sabadellProvider;
    }

    public function getFields(){
        return array(
            'amount','description','url_notification','url_ok','url_ko'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $description = $baseTransaction->getDataIn()['description'];
        $url_ok = $baseTransaction->getDataIn()['url_ok'];
        $url_ko = $baseTransaction->getDataIn()['url_ko'];
        $id=$baseTransaction->getId();
        $request=$this->getTransactionContext()->getRequestStack()->getCurrentRequest();
        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_final='/notifications/v2/sabadell/'.$id;

        $barcode = $this->sabadellProvider->request($amount,$id,$description,$url_base,$url_ok,$url_ko,$url_final);

        if($barcode === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($barcode);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function check(Transaction $transaction){
        $client_reference=$transaction->getId();

        $status=$this->sabadellProvider->status($client_reference);

        $transaction->setData($status);

        return $transaction;
    }

}