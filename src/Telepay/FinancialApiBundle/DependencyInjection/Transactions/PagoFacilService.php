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
use Telepay\FinancialApiBundle\Document\Transaction;


class PagoFacilService extends BaseService{

    private $pagofacilProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $pagofacilProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->pagofacilProvider = $pagofacilProvider;
    }

    public function getFields(){
        return array(
            'name',
            'surname',
            'card_number',
            'cvv',
            'cp',
            'expiration_month',
            'expiration_year',
            'amount',
            'email',
            'phone',
            'mobile_phone',
            'street_number',
            'colony',
            'city',
            'quarter',
            'country'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $name = $baseTransaction->getDataIn()['name'];
        $surname = $baseTransaction->getDataIn()['surname'];
        $card_number = $baseTransaction->getDataIn()['card_number'];
        $cvv = $baseTransaction->getDataIn()['cvv'];
        $cp = $baseTransaction->getDataIn()['cp'];
        $expiration_month = $baseTransaction->getDataIn()['expiration_month'];
        $expiration_year = $baseTransaction->getDataIn()['expiration_year'];
        $amount = $baseTransaction->getDataIn()['amount'];
        $email = $baseTransaction->getDataIn()['email'];
        $phone = $baseTransaction->getDataIn()['phone'];
        $mobile_phone = $baseTransaction->getDataIn()['mobile_phone'];
        $street_number = $baseTransaction->getDataIn()['street_number'];
        $colony = $baseTransaction->getDataIn()['colony'];
        $city = $baseTransaction->getDataIn()['city'];
        $quarter = $baseTransaction->getDataIn()['quarter'];
        $country = $baseTransaction->getDataIn()['country'];
        $id = $baseTransaction->getId();

        $pagofacil = $this->pagofacilProvider->request($name,$surname,$card_number,$cvv,$cp,$expiration_month,$expiration_year,$amount,$email,$phone,$mobile_phone,$street_number,$colony,$city,$quarter,$country,$id);

        $new_card_number=substr_replace($card_number, '************', 0, -4);
        $dataIn=$baseTransaction->getDataIn();
        $dataIn['card_number']=$new_card_number;

        if($pagofacil === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        if($pagofacil['authorization']==0) throw new HttpException(409,$pagofacil['error']);

        $baseTransaction->setData($pagofacil);
        $baseTransaction->setDataOut($pagofacil);

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){
        $client_reference=$transaction->getId();

        $status=$this->pagofacilProvider->status($client_reference);

        $transaction->setData($status);

        return $transaction;
    }
}