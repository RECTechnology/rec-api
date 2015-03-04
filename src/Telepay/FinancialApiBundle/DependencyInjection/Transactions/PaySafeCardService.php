<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\PaysafecardPayment;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class PaySafeCardService extends BaseService{

    private $paysafecardProvider;

    public function __construct($name, $cname, $role, $base64Image, $paysafecardProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $base64Image, $transactionContext);
        $this->paysafecardProvider = $paysafecardProvider;
    }

    public function getFields(){
        return array(
            'currency',
            'amount',
            'url_success',
            'url_fail',
            'url_notification'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $currency = $baseTransaction->getDataIn()['currency'];
        $amount = $baseTransaction->getDataIn()['amount'];
        $url_success = $baseTransaction->getDataIn()['url_success'];
        $url_fail = $baseTransaction->getDataIn()['url_fail'];
        $merchant_client_id = 'Telepay';

        $id=$baseTransaction->getId();

        $request=$this->getTransactionContext()->getRequestStack()->getCurrentRequest();
        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_final='/notifications/v1/paysafecard/'.$id;

        $psc = $this->paysafecardProvider->request($id,$currency,$amount,$url_success,$url_fail,$merchant_client_id, $url_base,$url_final);

        if($psc === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($psc);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }
}