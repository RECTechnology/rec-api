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

class EthMethod extends BaseMethod {

    private $driver;
    private $container;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier);
        $this->driver = $driver;
        $this->container = $container;
    }

    //PAY IN
    public function getPayInInfo($amount){
        $passphrase = $this->getPassphrase();
        $address = $this->driver->personal_newAccount(array($passphrase));
        if(isset($address['error'])) throw new Exception('Service Temporally unavailable', 503);
        $min_confirmations = $this->container->getParameter('eth_min_confirmations');
        $response = array(
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'address' => $address,
            'passphrase' => $passphrase,
            'expires_in' => intval(1200),
            'received' => 0.0,
            'min_confirmations' => intval($min_confirmations),
            'confirmations' => 0,
            'status'    =>  'created',
            'final'     =>  false
        );
        return $response;
    }

    public function getCurrency(){
        return Currency::$ETH;
    }

    public function getPayInStatus($paymentInfo){
        $allReceived = $this->driver->listreceivedbyaddress(0, true);
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
    public function getPayOutInfo($request){
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
            $params['concept'] = 'Eth out Transaction';
        }

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
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
            $params['concept'] = 'Eth out Transaction';
        }
        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;
        return $params;
    }

    public function send($paymentInfo){
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

    public function getPayOutStatus($id){
        // TODO: Implement getPayOutStatus() method.
    }

    public function cancel($payment_info){
        throw new Exception('Method not implemented', 409);
    }

    public function getReceivedByAddress($address){
        $allReceived = $this->driver->getreceivedbyaddress($address, 0);
        return $allReceived;
    }

    public function getInfo(){
        $info = $this->driver->getinfo();
        return $info;
    }

    private function getPassphrase(){
        $chars = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";
        $array_chars = str_split($chars);
        shuffle($array_chars);
        return substr(implode("", $array_chars),0,5);
    }
}