<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Services\Libs;


require_once 'lib/PayU.php';

use PaymentMethods;
use PayUCountries;
use PayUParameters;
use PayUPayments;

/**
 *
 */
class PayUPayment
{
    var $account_id;
    var $installments_number;
    var $payer_name;
    var $country;
    var $currency;
    var $reference_code;
    var $description;
    var $value;
    var $pay_method;
    var $card_number;
    var $expiration_date;
    var $security_code;
    var $without_cvv2;
    var $tax_base;
    var $tax_value;
    var $dni;

    function __construct($account_id,$installments_number,$payer_name,$country,$currency,$reference_code,$description,$value,$pay_method)
    {
        $this->account_id=$account_id;
        $this->installments_number=$installments_number;
        $this->payer_name=$payer_name;
        $this->country=$country;
        $this->currency=$currency;
        $this->reference_code=$reference_code;
        $this->description=$description;
        $this->value=$value;
        $this->pay_method=$pay_method;
    }

    public function transaction($card_number,$expiration_date,$security_code,$without_cvv2,$tax_base,$tax_value){

        $this->card_number=$card_number;
        $this->expiration_date=$expiration_date;
        $this->security_code=$security_code;
        $this->without_cvv2=$without_cvv2;
        $this->tax_base=$tax_base;
        $this->tax_value=$tax_value;

        $parameters = array(
            // Ingrese aquí el nombre del comprador
            PayUParameters::PAYER_NAME => $this->payer_name,
            PayUParameters::INSTALLMENTS_NUMBER => $this->installments_number,
            // Ingrese aquí el nombre del pais.
            PayUParameters::COUNTRY => PayUCountries::$this->country,
            // Ingrese aquí el identificador de la cuenta.
            PayUParameters::ACCOUNT_ID => $this->account_id,
            // Cookie de la sesión actual.
            PayUParameters::PAYER_COOKIE => "cookie_" . time (),
            // Valores
            // Ingrese aquí la moneda.
            PayUParameters::CURRENCY => $this->currency,
            //Ingrese aquí el código de referencia.
            PayUParameters::REFERENCE_CODE => $this->reference_code,
            //Ingrese aquí la descripción.
            PayUParameters::DESCRIPTION => $this->description,
            //Ingrese aquí el valor.
            PayUParameters::VALUE => $this->value,
            //Ingrese aquí su firma.
            PayUParameters::SIGNATURE => "575522081b12448a6a0cf326716a9c13",
            // Datos de la tarjeta de crédito
            //Ingrese aquí el número de la tarjeta de crédito
            PayUParameters::CREDIT_CARD_NUMBER => $this->card_number,
            //Ingrese aquí la fecha de vencimiento de la tarjeta de crédito.
            PayUParameters::CREDIT_CARD_EXPIRATION_DATE => $this->expiration_date,
            //Ingrese aquí el código de seguridad de la tarjeta de crédito.
            PayUParameters::CREDIT_CARD_SECURITY_CODE => $this->security_code,
            //Ingrese aquí el nombre de la tarjeta de crédito.
            PayUParameters::PAYMENT_METHOD => PaymentMethods::$this->pay_method,
            //OPCIONAL Ingrese "true" si no debe ser procesado el código de seguridad.
            PayUParameters::PROCESS_WITHOUT_CVV2 , $this->without_cvv2,
            // Si la operación es de Colombia, se deben definir Impuestos (IVA).
            //Ingrese aquí la base de devolución del IVA.
            PayUParameters::TAX_RETURN_BASE => $this->tax_base,
            //Ingrese aquí el valor del IVA.
            PayUParameters::TAX_VALUE => $this->tax_value
        );

        $result = PayUPayments::doAuthorizationAndCapture($parameters);
        $result=get_object_vars($result);
        $result['transactionResponse']=get_object_vars($result['transactionResponse']);
        //var_dump($result);
        return($result);
    }

    public function payment($dni){

        $this->dni=$dni;

        $parameters = array(
            //Ingrese aquí el nombre del comprador
            PayUParameters::PAYER_NAME => $this->payer_name,
            //Ingrese aquí el número de cuotas.
            PayUParameters::INSTALLMENTS_NUMBER => $this->installments_number,
            //Ingrese aquí el nombre del pais.
            PayUParameters::COUNTRY => PayUCountries::$this->country,
            //Ingrese aquí el identificador de la cuenta.
            PayUParameters::ACCOUNT_ID => $this->account_id,
            //Cookie de la sesión actual.
            PayUParameters::PAYER_COOKIE => "cookie_".time(),
            // Valores
            //Ingrese aquí la moneda.
            PayUParameters::CURRENCY => $this->currency,
            //Ingrese aquí el código de referencia.
            PayUParameters::REFERENCE_CODE => $this->reference_code,
            //Ingrese aquí la descripción.
            PayUParameters::DESCRIPTION => $this->description,
            //Ingrese aquí el valor.
            PayUParameters::VALUE => $this->value,
            //Ingrese aquí su firma.
            PayUParameters::SIGNATURE => "575522081b12448a6a0cf326716a9c13",
            //Ingrese aquí el nombre del medio de pago en efectivo (Ejm: BALOTO, OXXO)
            PayUParameters::PAYMENT_METHOD=> PaymentMethods::$this->pay_method,
            PayUParameters::PAYER_DNI => $this->dni
        );
        $result = PayUPayments::doAuthorizationAndCapture($parameters);
        $result=get_object_vars($result);
        $result['transactionResponse']=get_object_vars($result['transactionResponse']);
        //var_dump($result);
        //var_dump($result);
        return $result;

    }
}
