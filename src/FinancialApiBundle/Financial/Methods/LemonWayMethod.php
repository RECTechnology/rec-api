<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace App\FinancialApiBundle\Financial\Methods;

use App\FinancialApiBundle\Document\Transaction;
use FOS\OAuthServerBundle\Util\Random;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use App\FinancialApiBundle\Financial\Currency;

class LemonWayMethod extends BaseMethod {

    private $driver;
    private $container;
    private $minimum;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier, $default_fixed_fee, $default_variable_fee, $minimum){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier, $default_fixed_fee, $default_variable_fee);
        $this->driver = $driver;
        $this->container = $container;
        $this->minimum = $minimum;
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

    public function RegisterWalletIndividual($wallet, $email, $name, $lastName, $date_birth, $nationality, $gender, $address, $zip, $city, $country){
        $response = $this->driver->callService("RegisterWallet", array(
            "wallet" => $wallet,
            "clientMail" => $email,
            "clientFirstName" => $name,
            "clientLastName" => $lastName,
            "birthdate" => $date_birth,
            "nationality" => $nationality,
            "clientTitle" => $gender,
            "street" => $address,
            "postCode" => $zip,
            "city" => $city,
            "ctry" => $country,
            "payerOrBeneficiary" => 2
        ));
        return $response;
    }

    public function RegisterWalletCompany($wallet, $email, $company_name, $company_web, $company_description, $name, $lastName, $date_birth, $nationality, $gender, $address, $zip, $city, $country){
        $response = $this->driver->callService("RegisterWallet", array(
            "wallet" => $wallet,
            "clientMail" => $email,
            "isCompany" => '1',
            "companyName" => $company_name,
            "companyWebsite" => $company_web,
            "companyDescription" => $company_description,
            "clientFirstName" => $name,
            "clientLastName" => $lastName,
            "birthdate" => $date_birth,
            "nationality" => $nationality,
            "clientTitle" => $gender,
            "street" => $address,
            "postCode" => $zip,
            "city" => $city,
            "ctry" => $country,
            "payerOrBeneficiary" => 2
        ));
        return $response;
    }

    public function UploadFile($wallet, $fileName, $type, $buffer){
        $response = $this->driver->callService("UploadFile", array(
            "wallet" => $wallet,
            "fileName" => $fileName,
            "type" => $type,
            "buffer" => $buffer
        ));
        return $response;
    }

    public function UpdateIdentification($old_id, $new_id){
        $response = $this->driver->callService("UpdateWalletDetails", array(
            "wallet" => $old_id,
            "newCompanyIdentificationNumber" => $new_id
        ));
        return $response;
    }

    public function CreditCardPayment($amount, $token, $save = false, $card_id = null){
        $admin = $this->container->getParameter('lemonway_admin_account');
        $notification_url = $this->container->getParameter('lemonway_notification_url');
        $requestData = array(
            "wallet" => $admin,
            "amountTot" => $amount,
            "wkToken" => $token,
            "returnUrl" => $notification_url . "ok",
            "errorUrl" => $notification_url . "error",
            "cancelUrl" => $notification_url . "cancel",
            "registerCard" => $save?"1":"0"
        );

        if($card_id){
            $requestData['cardId'] = $card_id;
        }
        return $this->driver->callService("MoneyInWebInit", $requestData);
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

    public function getPayInInfo($account_id, $amount){
        //$amount = $data['amount']/pow(10, Currency::$SCALE[$this->getCurrency()]);
        //$payment_info = $this->CreditCardPayment($amount);
        //if(!$payment_info) throw new Exception('Service Temporally unavailable', 503);
        throw new Exception('Service Temporally unavailable', 503);
    }

    public function notification($params, $paymentInfo){
        $paymentInfo['status'] = 'received';
        if($params['response_code'] != "0000"){
            $paymentInfo['status'] = 'failed';
            $paymentInfo['concept'] = 'error resp code(' . $params['response_code'] . ')';
        }
        if(intval($paymentInfo['amount'])/pow(10, Currency::$SCALE[$this->getCurrency()]) != floatval($params['response_transactionAmount'])) {
            //$paymentInfo['status'] = 'failed';
            //$paymentInfo['concept'] = 'error amount(' . intval($params['response_transactionAmount']) . '!=' . floatval($paymentInfo['amount'])/pow(10, Currency::$SCALE[$this->getCurrency()]) .')';
        }
        return $paymentInfo;
    }

    public function GetWalletDetails(){
        $admin = $this->container->getParameter('lemonway_admin_account');
        $response = $this->driver->callService("GetWalletDetails", array(
            "wallet" => $admin
        ));
        return $response;
    }

    public function GetBalances(){
        //miramos solo los que se han modificado el último día (con 10 minutos de margen)
        $lemonway_sync_balance_last = $this->container->getParameter('lemonway_sync_balance_last');
        $now = time()-$lemonway_sync_balance_last;
        $response = $this->driver->callService("GetBalances", array(
            "updateDate" => $now
        ));
        $response = json_decode(json_encode($response), true);
        return $response;
    }

    public function getMinimumAmount(){
        return $this->minimum;
    }

    public function GetCardAlias($card_id){
        $data = $this->GetWalletDetails();
        $data_array = json_decode(json_encode($data), true);
        foreach ($data_array['WALLET']['CARDS'] as $card){
            if($card['ID'] === $card_id){
                return $card['EXTRA']['NUM'];
            }
        }
        return "temp";
    }

    public function getPayInInfoWithCommerce($data){
        $amount = round($data['amount']/pow(10, Currency::$SCALE[$this->getCurrency()]),2);
        $amount = number_format((float)$amount, 2, '.', '');
        $token = substr(Random::generateToken(), 0, 8);

        $card_id = null;
        if(isset($data['card_id'])){
            $card_id = $data['card_id'];
        }
        $payment_info = $this->CreditCardPayment($amount, $token, $data['save_card'], $card_id);
        $url = $this->container->getParameter('lemonway_payment_url');
        $error = false;
        if(!property_exists($payment_info, 'MONEYINWEB') && isset($payment_info['MONEYINWEBINIT']) && isset($payment_info['MONEYINWEBINIT']['STATUS']) && $payment_info['MONEYINWEBINIT']['STATUS']=='-1'){
            $error = true;
        }
        $response = array(
            'amount' => $data['amount'],
            'commerce_id' => $data['commerce_id'],
            'currency' => $this->getCurrency(),
            'scale' => Currency::$SCALE[$this->getCurrency()],
            'token_id' => $payment_info->MONEYINWEB->TOKEN,
            'payment_url' => $url . $payment_info->MONEYINWEB->TOKEN,
            'payment_info' => json_encode($payment_info),
            'external_card_id' => $payment_info->MONEYINWEB->CARD->ID,
            'save_card' => $data['save_card'],
            'wl_token' => $token,
            'transaction_id' => $payment_info->MONEYINWEB->ID,
            'expires_in' => intval(1200),
            'received' => 0.0,
            'status' => 'created',
            'final' => false
        );
        if ($error){
            unset(
                $response['token_id'],
                $response['payment_url'],
                $response['card_id'],
                $response['save_card'],
                $response['transaction_id']
            );
            $response['status'] = 'failed';
            $response['final'] = true;
        }
        else {
            unset($response['payment_info']);
        }

        return $response;
    }

    public function getPayInStatus($paymentInfo){
        return $paymentInfo;
    }

    public function getPayOutStatus($id){
    }

    /**
     * @param $paymentInfo
     */
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
        $paymentInfo['status']= $data['TRANS_SENDPAYMENT']['HPAY']['STATUS'] ?? $data['SENDPAYMENT']['STATUS'];
        if($paymentInfo['status'] === '0') {
            $paymentInfo['id'] = $data['TRANS_SENDPAYMENT']['HPAY']['ID'];
            $paymentInfo['status'] = Transaction::$STATUS_SENDING;
            $paymentInfo['final'] = false;
        }
        elseif($paymentInfo['status'] === '3'){
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

    public function cardInfo($card_id){
        return array(
            'id' => $card_id,
            'alias' => $this->GetCardAlias($card_id)
        );
    }
}