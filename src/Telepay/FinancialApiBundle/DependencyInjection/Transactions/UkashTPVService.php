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
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\UkashRedirect;
use Telepay\FinancialApiBundle\Document\Transaction;

class UkashTPVService extends BaseService{

    private $ukashProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $ukashProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->ukashProvider = $ukashProvider;
    }

    public function getFields(){
        return array(
            'amount',
            'currency',
            'url_success',
            'url_fail',
            'url_notification'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = round($baseTransaction->getDataIn()['amount']/100,2);
        $currency = $baseTransaction->getDataIn()['currency'];
        $url_success = $baseTransaction->getDataIn()['url_success'];
        $url_fail = $baseTransaction->getDataIn()['url_fail'];
        $consumer_id='Telepay';

        //todo hay que quitar esta id porque el servicio de ukash no se la traga
        //$id=$baseTransaction->getId();
        $id='2345H411111111111111';

        $request=$this->getTransactionContext()->getRequestStack()->getCurrentRequest();
        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_final='/notifications/v1/ukash/'.$id;

        $ukash = $this->ukashProvider->request($amount,$id,$consumer_id,$currency,$url_success,$url_fail,$url_base,$url_final);

        if($ukash === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($ukash);
        $baseTransaction->setDataOut($ukash);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }
}