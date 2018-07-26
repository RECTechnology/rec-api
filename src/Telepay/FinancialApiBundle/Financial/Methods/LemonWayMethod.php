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

    public function CreditCardPayment($amount, $save = false){
        $admin = $this->container->getParameter('lemonway_admin_account');
        $notification_url = $this->container->getParameter('lemonway_notification_url');
        $response = $this->driver->callService("MoneyInWebInit", array(
            "wallet" => $admin,
            "amountTot" => $amount,
            "returnUrl" => $notification_url . "ok",
            "errorUrl" => $notification_url . "error",
            "cancelUrl" => $notification_url . "cancel",
            "registerCard" => $save?"1":"0"
        ));
        return $response;
    }

    public function SavedCreditCardPayment($amount, $card_id){
        $admin = $this->container->getParameter('lemonway_admin_account');
        $response = $this->driver->callService("MoneyInWithCardId", array(
            "wallet" => $admin,
            "amountTot" => $amount,
            "isPreAuth" => '0',
            "cardId" => $card_id
        ));
        return $response;
    }

    public function getPayInInfo($amount){
        //$amount = $data['amount']/pow(10, Currency::$SCALE[$this->getCurrency()]);
        //$payment_info = $this->CreditCardPayment($amount);
        //if(!$payment_info) throw new Exception('Service Temporally unavailable', 503);
        throw new Exception('Service Temporally unavailable', 503);
    }

    public function notification($params, $paymentInfo){
        $paymentInfo['status'] = 'received';
        if($paymentInfo['response_code'] != "0000") $paymentInfo['status'] = 'failed';
        if(intval($paymentInfo['amount']) != intval($params['response_transactionAmount'])) $paymentInfo['status'] = 'failed';
        return $paymentInfo;
    }

    public function GetWalletDetails(){
        //GetWalletDetails
    }

    public function getPayInInfoWithCommerce($data){
        $amount = round($data['amount']/pow(10, Currency::$SCALE[$this->getCurrency()]),2);
        $amount = number_format((float)$amount, 2, '.', '');
        if(isset($data['card_id'])){
            $payment_info = $this->SavedCreditCardPayment($amount, $data['card_id']);
            if(!$payment_info) throw new Exception('Service Temporally unavailable', 503);
            $response = array(
                'amount' => $data['amount'],
                'commerce_id' => $data['commerce_id'],
                'currency' => $this->getCurrency(),
                'scale' => Currency::$SCALE[$this->getCurrency()],
                'card_id' => $payment_info->MONEYINWEB->CARD->ID,
                'transaction_id' => $payment_info->MONEYINWEB->ID,
                'expires_in' => intval(1200),
                'received' =>  $data['amount'],
                'status' => 'received',
                'final' => false
            );
        }
        else {
            $payment_info = $this->CreditCardPayment($amount, $data['save_card']);
            $status = 'created';
            $url = $this->container->getParameter('lemonway_payment_url');
            $response = array(
                'amount' => $data['amount'],
                'commerce_id' => $data['commerce_id'],
                'currency' => $this->getCurrency(),
                'scale' => Currency::$SCALE[$this->getCurrency()],
                'token_id' => $payment_info->MONEYINWEB->TOKEN,
                'payment_url' => $url . $payment_info->MONEYINWEB->TOKEN,
                'payment_info' => json_encode($payment_info),
                'card_id' => $payment_info->MONEYINWEB->CARD->ID,
                'error_id' => $payment_info['MONEYINWEBINIT']['STATUS'],
                'save_card' => $data['save_card'],
                'transaction_id' => $payment_info->MONEYINWEB->ID,
                'expires_in' => intval(1200),
                'received' => 0.0,
                'status' => $status,
                'final' => false
            );
            if (intval($response['error_id']) == -1){
                unset($response['token_id']);
                unset($response['payment_url']);
                unset($response['card_id']);
                unset($response['save_card']);
                unset($response['transaction_id']);
                $response['final'] = true;
            }
            unset($response['error_id']);
            unset($response['payment_info']);
        }
        return $response;
    }

    public function getPayInStatus($paymentInfo){
    }

    public function getPayOutStatus($id){
    }

    public function send($paymentInfo){
        if(isset($paymentInfo['from'])) {
            $from = $paymentInfo['from'];
        }
        else {
            $from = $this->container->getParameter('lemonway_admin_account');
        }
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