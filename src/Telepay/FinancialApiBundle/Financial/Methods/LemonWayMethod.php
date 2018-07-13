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

class LemonWayMethod extends BaseMethod {

    private $driver;
    private $container;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier, $default_fixed_fee, $default_variable_fee){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier, $default_fixed_fee, $default_variable_fee);
        $this->driver = $driver;
        $this->container = $container;
    }

    public function RegisterWallet($wallet, $email, $name, $lastName, $gender){
        $response = $this->driver->callService("RegisterWallet", array(
            "wallet" => $wallet,
            "clientMail" => $email,
            "clientFirstName" => $name,
            "clientLastName" => $lastName,
            "clientTitle" => $gender
        ));
        return $response;
    }

    public function CreditCardPayment($amount){
        $response = $this->driver->callService("MoneyInWebInit", array(
            "wallet" => 'ADMIN',
            "amountTot" => $amount,
            "registerCard" => "1"
        ));
        return $response;
    }

    public function SavedCreditCardPayment($amount, $card_id){
        $response = $this->driver->callService("MoneyInWithCardId", array(
            "wallet" => 'ADMIN',
            "amountTot" => $amount,
            "isPreAuth" => '0',
            "cardId" => $card_id
        ));
        return $response;
    }

    public function getPayInInfo($amount){
        $payment_info = $this->CreditCardPayment($amount);
        if(!$payment_info) throw new Exception('Service Temporally unavailable', 503);
        $response = array(
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'payment_url' => $payment_info['url'],
            'expires_in' => intval(1200),
            'received' => 0.0,
            'status'    =>  'created',
            'final'     =>  false
        );
        return $response;
    }

    public function getPayInInfoWithCommerce($data){
        $payment_info = $this->CreditCardPayment($data['amount']);
        if(!$payment_info) throw new Exception('Service Temporally unavailable', 503);
        $response = array(
            'amount'    =>  $data['amount'],
            'commerce_id'    =>  $data['commerce_id'],
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'payment_url' => $payment_info->MONEYINWEB->TOKEN,
            'card_id' => $payment_info->MONEYINWEB->CARD->ID,
            'transaction_id' => $payment_info->MONEYINWEB->ID,
            'response' => json_encode($payment_info, JSON_PRETTY_PRINT),
            'expires_in' => intval(1200),
            'received' => 0.0,
            'status'    =>  'created',
            'final'     =>  false
        );
        return $response;
    }

    public function getPayInStatus($paymentInfo){
    }

    public function getPayOutStatus($id){
    }

    public function send($paymentInfo){
        $from = $paymentInfo['from'];
        $to = $paymentInfo['to'];
        $amount = $paymentInfo['amount'];

        $data = $this->driver->callService("SendPayment", array(
            "debitWallet" => $from,
            "creditWallet" => $to,
            "amount" => $amount
        ));
        $data = json_decode(json_encode($data), true);
        $paymentInfo['status']=isset($data['TRANS_SENDPAYMENT']['HPAY']['STATUS'])?$data['TRANS_SENDPAYMENT']['HPAY']['STATUS']:$data['SENDPAYMENT']['STATUS'];
        if($paymentInfo['status'] == '0') {
            $paymentInfo['id'] = $data['TRANS_SENDPAYMENT']['HPAY']['ID'];
            $paymentInfo['status'] = Transaction::$STATUS_SENDING;
            $paymentInfo['final'] = false;
        }
        elseif($paymentInfo['status'] == '3'){
            $paymentInfo['id'] = $data['TRANS_SENDPAYMENT']['HPAY']['ID'];
            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
            $paymentInfo['final'] = true;
        }else{
            //110 balance is not enough
            $paymentInfo['error'] = $data['SENDPAYMENT']['ERROR'];
            $paymentInfo['error_message'] = $data['SENDPAYMENT']['MESSAGE'];
            $paymentInfo['status'] = Transaction::$STATUS_ERROR;
            $paymentInfo['final'] = true;
        }
        return $paymentInfo;
    }

    public function cancel($payment_info){
        throw new Exception('Method not implemented', 409);
    }


    public function getInfo(){
    }
}