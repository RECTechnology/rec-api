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

    public function CreditCardPayment($wallet, $amount){
        $response = $this->driver->callService("MoneyInWebInit", array(
            "wallet" => $wallet,
            "amountTot" => $amount,
            "registerCard" => "1"
        ));
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

        try {
            $data = $this->driver->callService("SendPayment", array(
                "debitWallet" => $from,
                "creditWallet" => $to,
                "amount" => $amount
            ));
        } catch (Exception $e) {
            $data['SENDPAYMENT']['STATUS'] = '-1';
            $data['SENDPAYMENT']['ID'] = '-1';
            $paymentInfo['error'] = $e->getMessage();
        }

        $paymentInfo['id'] = $data['SENDPAYMENT']['ID'];
        $paymentInfo['status'] = $data['SENDPAYMENT']['STATUS'];
        if($paymentInfo['status'] == '0') {
            $paymentInfo['status'] = Transaction::$STATUS_SENDING;
            $paymentInfo['final'] = false;
        }
        elseif($paymentInfo['status'] == '3'){
            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
            $paymentInfo['final'] = true;
        }else{
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