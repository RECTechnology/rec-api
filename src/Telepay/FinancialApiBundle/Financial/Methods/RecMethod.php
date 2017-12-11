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

class RecMethod extends BaseMethod {

    private $driver;
    private $container;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier, $default_fixed_fee, $default_variable_fee){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier, $default_fixed_fee, $default_variable_fee);
        $this->driver = $driver;
        $this->container = $container;
    }

    //PAY IN
    public function getPayInInfo($amount){
        //$address = $this->driver->getnewaddress();
        $address = "r" . substr(Random::generateToken(), 0, 33);
        if(!$address) throw new Exception('Service Temporally unavailable', 503);
        $min_confirmations = $this->container->getParameter('rec_min_confirmations');
        $response = array(
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'address' => $address,
            'expires_in' => intval(1200),
            'received' => 0.0,
            'min_confirmations' => intval($min_confirmations),
            'confirmations' => 0,
            'status'    =>  'created',
            'final'     =>  false
        );
        return $response;
    }

    public function getPayInInfoWithData($data){
        $address = $data;
        if(!$address) throw new Exception('Service Temporally unavailable', 503);
        $min_confirmations = $this->container->getParameter('rec_min_confirmations');
        $response = array(
            'amount'    =>  $data['amount'],
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'address' => $data['address'],
            'expires_in' => intval(1200),
            'received' => $data['amount'],
            'txid' => $data['txid'],
            'min_confirmations' => intval($min_confirmations),
            'confirmations' => 0,
            'status'    =>  'received',
            'final'     =>  false
        );
        return $response;
    }


    public function getCurrency()
    {
        return Currency::$REC;
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

        //TODO
        //$address_verification = $this->driver->validateaddress($params['address']);
        $address_verification['isvalid'] = true;

        if(!$address_verification['isvalid']) throw new Exception('Invalid address.', 400);

        if($request->request->has('concept')){
            $params['concept'] = $request->request->get('concept');
        }else{
            $params['concept'] = 'Rec out Transaction';
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

        //TODO
        //$address_verification = $this->driver->validateaddress($params['address']);
        $address_verification['isvalid'] = true;

        if(!$address_verification['isvalid']) throw new Exception('Invalid address.', 400);

        //TODO
        //if($this->driver->getbalance() <= $params['amount'] / 1e8) throw new HttpException(403, 'Service Temporally unavailable');

        if(array_key_exists('concept', $data)) {
            $params['concept'] = $data['concept'];
        }else{
            $params['concept'] = 'Rec out Transaction';
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

        //$crypto = $this->driver->sendtoaddress($address, $amount/1e8);
        $crypto = substr(Random::generateToken(), 0, 48);

        $response = array();
        $response['address'] = $paymentInfo['address'];
        $response['amount'] = $paymentInfo['amount'];

        if($crypto === false){
            $response['status'] = Transaction::$STATUS_FAILED;
            $response['final'] = false;
        }else{
            $response['txid'] = $crypto;
            $response['status'] = 'sent';
            $response['final'] = true;
        }
        return $response;
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

    public function getInfo(){
        $info = $this->driver->getinfo();

        return $info;
    }


}