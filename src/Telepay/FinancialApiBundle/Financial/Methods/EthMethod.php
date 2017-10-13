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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;

class EthMethod extends BaseMethod {

    private $driver;
    private $container;
    private $ignored_scale = 10;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier, $default_fixed_fee, $default_variable_fee){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier, $default_fixed_fee, $default_variable_fee);
        $this->driver = $driver;
        $this->container = $container;
    }

    //PAY IN
    public function getPayInInfo($amount){
        $passphrase = $this->getPassphrase();
        $address = $this->driver->personal_newAccount($passphrase);
        if(isset($address['error'])) throw new Exception('Service Temporally unavailable', 503);
        $min_confirmations = $this->container->getParameter('eth_min_confirmations');
        $response = array(
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'address' => $address,
            'block' => 0,
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
        $totalReceivedHex = $this->driver->eth_getBalance($paymentInfo['address'], 'latest');
        $totalReceived = hexdec($totalReceivedHex)/pow(10, $this->ignored_scale);
        $amount = $paymentInfo['amount'];

        if($amount <= 100)
            $margin = 0;
        elseif($amount > 100000000)
            $margin = 10000;
        else
            $margin = 100;

        $allowed_amount = $amount - $margin;
        $status = 'created';
        $blockNumber = $this->driver->eth_blockNumber();

        if(doubleval($totalReceived)>$paymentInfo['received']){
            $paymentInfo['received'] = doubleval($totalReceived);
            $paymentInfo['block'] = hexdec($blockNumber);
        }

        if(doubleval($totalReceived) >= $allowed_amount){
            $status = 'received';
            $num_confirmaciones = hexdec($blockNumber) - $paymentInfo['block'];
            $paymentInfo['confirmations'] = $num_confirmaciones;
            if($paymentInfo['confirmations'] >= $paymentInfo['min_confirmations']){
                $status = 'success';
                $final = true;
                $paymentInfo['final'] = $final;
            }
        }
        $paymentInfo['status'] = $status;
        return $paymentInfo;
    }

    //PAY OUT
    public function getPayOutInfo($request){
        $paramNames = array(
            'amount',
            'address',
            'email'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(400, 'Parameter '.$param.' not found');
            if($request->request->get($param) == null) throw new Exception( 'Parameter '.$param.' can\'t be null', 404);
            $params[$param] = $request->request->get($param);

        }

        $address_verification = $this->validateaddress($params['address']);

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

        $address_verification = $this->validateaddress($params['address']);

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
        $to_address = $paymentInfo['address'];
        $amount = $paymentInfo['amount'] * pow(10, $this->ignored_scale);
        $from_address = $this->container->getParameter('eth_main_address');
        $from_address_pass = $this->container->getParameter('eth_main_passphrase');

        $crypto = $this->driver->personal_sendTransaction($from_address, $to_address, $amount, $from_address_pass);

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

    public function sendInternal($from_address, $amount){
        $to_address = $this->container->getParameter('eth_main_address');
        $amount = $amount * pow(10, $this->ignored_scale);
        $from_address_pass = $this->getPassphrase();

        $crypto = $this->driver->personal_sendTransaction($from_address, $to_address, $amount, $from_address_pass);

        if($crypto === false){
            return false;
        }else {
            return true;
        }
    }

    public function getPayOutStatus($id){
    }

    public function cancel($payment_info){
        throw new Exception('Method not implemented', 409);
    }

    public function getReceivedByAddress($address){
        $allReceived = $this->driver->eth_getBalance($address, 'latest');
        return hexdec($allReceived)/pow(10, $this->ignored_scale);
    }

    public function getInfo(){
        $address = $this->container->getParameter('eth_main_address');
        $balance = $this->driver->eth_getBalance($address, 'latest');
        $info = array(
            'balance' => hexdec($balance)/pow(10, $this->ignored_scale)
        );
        return $info;
    }

    private function getPassphrase(){
        return $this->container->getParameter('eth_standard_passphrase');
    }

    private function validateaddress($address){
        $address_verification = array();
        $rest = substr($address, 0, 2);
        $address_verification['isvalid'] = false;
        if ($rest == "0x" && strlen($address)==42) {
            $address_verification['isvalid'] = true;
        }
        return $address_verification;
    }

    /**
     * @return Boolean
     */
    public function checkKYC(Request $request, $type){

        if($type == 'in'){
            return $request;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();

        $email = $request->request->get('email');
        $pass = $request->request->get('password');
        $factory = $this->getContainer()->get('security.encoder_factory');
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'email' => $email
        ));
        if(!$user){
            throw new HttpException(400, "Email is not registred");
        }
        $encoder = $factory->getEncoder($user);
        $bool = ($encoder->isPasswordValid($user->getPassword(), $pass, $user->getSalt())) ? true : false;

        if(!$bool){
            throw new HttpException(400, "Email or Password not correct");
        }

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if(!$kyc){
            throw new Exception('User without kyc information',400);
        }

        if(!$kyc->getEmailValidated()){
            throw new Exception('Email must be validated.',400);
        }

        return $request;
    }

}