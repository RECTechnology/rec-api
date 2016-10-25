<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use FOS\OAuthServerBundle\Util\Random;
use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;

class BtcMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $container);
        $this->driver = $driver;
    }

    //PAY IN
    public function getPayInInfo($amount){
        $address = $this->driver->getnewaddress();
        if(!$address) throw new Exception('Service Temporally unavailable', 503);
        $response = array(
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'address' => $address,
            'expires_in' => intval(1200),
            'received' => 0.0,
            'min_confirmations' => intval(1),
            'confirmations' => 0,
            'status'    =>  'created',
            'final'     =>  false
        );
        return $response;
    }

    public function getCurrency()
    {
        return Currency::$BTC;
    }

    public function getPayInStatus($paymentInfo)
    {
        $allReceived = $this->driver->listreceivedbyaddress(0, true);
//        $allReceived = $cryptoProvider->getreceivedbyaddress($address, 0);

        $amount = $paymentInfo['amount'];
        $address = $paymentInfo['address'];

        if($amount <= 100)
            $margin = 0;
        else
            $margin = 100;

        $allowed_amount = $amount - $margin;
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] === $address){
                $paymentInfo['received'] = doubleval($cryptoData['amount'])*1e8;
                if(doubleval($cryptoData['amount'])*1e8 >= $allowed_amount){
                    $paymentInfo['confirmations'] = $cryptoData['confirmations'];
                    if($paymentInfo['confirmations'] >= $paymentInfo['min_confirmations']){
                        $status = 'success';
                        $final = true;
                        $paymentInfo['final'] = $final;
                    }else{
                        $status = 'received';
                    }

                }else{
                    $status = 'created';
                }

                $paymentInfo['status'] = $status;
                return $paymentInfo;

            }

        }

    }

    //PAY OUT
    public function getPayOutInfo($request)
    {
        $paramNames = array(
            'amount',
            'address'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(400, 'Parameter '.$param.' not found');
            if($request->request->get($param) == null) throw new Exception( 'Parameter '.$param.' can\'t be null', 404);
            $params[$param] = $request->request->get($param);

        }

        $address_verification = $this->driver->validateaddress($params['address']);

        if(!$address_verification['isvalid']) throw new Exception('Invalid address.', 400);

        if($request->request->has('concept')){
            $params['concept'] = $request->request->get('concept');
        }else{
            $params['concept'] = 'Btc out Transaction';
        }

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 7);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;


        return $params;
    }

    public function getPayOutInfoData($data){
        $paramNames = array(
            'amount',
            'address'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!array_key_exists($param, $data)) throw new HttpException(404, 'Parameter '.$param.' not found');
            if($data[$param] == null) throw new Exception( 'Parameter '.$param.' can\'t be null', 404);
            $params[$param] = $data[$param];

        }

        $address_verification = $this->driver->validateaddress($params['address']);

        if(!$address_verification['isvalid']) throw new Exception('Invalid address.', 400);

        if(array_key_exists('concept', $data)) {
            $params['concept'] = $data['concept'];
        }else{
            $params['concept'] = 'Btc out Transaction';
        }

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 7);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;

        return $params;
    }

    public function send($paymentInfo)
    {
        $address = $paymentInfo['address'];
        $amount = $paymentInfo['amount'];

        $crypto = $this->driver->sendtoaddress($address, $amount/1e8);

        if($crypto === false){
            $paymentInfo['status'] = Transaction::$STATUS_FAILED;
            $paymentInfo['final'] = false;
        }else{
            $paymentInfo['txid'] = $crypto;
            $paymentInfo['status'] = 'sent';
            $paymentInfo['final'] = true;
        }

        return $paymentInfo;
    }

    public function getPayOutStatus($id)
    {
        // TODO: Implement getPayOutStatus() method.
    }

    public function cancel($payment_info){
        throw new Exception('Method not implemented', 409);
    }

    public function getReceivedByAddress($address){
        $allReceived = $this->driver->getreceivedbyaddress($address, 0);

//        $receivedByAddress = array();
//        foreach($allReceived as $received){
//            if($received['address'] == $address){
//                $receivedByAddress[] = $received;
//            }
//        }

        return $allReceived;
    }


}