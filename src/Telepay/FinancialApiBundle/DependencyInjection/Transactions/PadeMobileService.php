<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\PademobileRedirect;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;


class PadeMobileService extends BaseService{

    private $pademobileProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $pademobileProvider, $transactionContext){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $transactionContext);
        $this->pademobileProvider = $pademobileProvider;
    }

    public function getFields(){
        return array(
            'country','url','description','amount'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $country = $baseTransaction->getDataIn()['country'];
        $description = $baseTransaction->getDataIn()['description'];
        $amount = $baseTransaction->getDataIn()['amount'];

        $id=$baseTransaction->getId();

        $request=$this->getTransactionContext()->getRequestStack()->getCurrentRequest();
        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_final='/notifications/v1/pademobile/'.$id;

        $pade = $this->pademobileProvider->request($amount,$country,$description, $url_base,$url_final);

        if($pade === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($pade);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function check(Transaction $transaction){
        $client_reference=$transaction->getId();

        $status=$this->pademobileProvider->status($client_reference);

        $transaction->setData($status);

        return $transaction;
    }
}