<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use PayUPayment;
use PayUPaymentTest;
use PayUReport;
use PayUReportTest;

class Payu{

    private $varArray =array(
        'installments_number'   =>  '3',
        'account_id'            =>  '500547'
    );

    public function getPayUPaymentTest($payer,$country,$currency,$reference_code,$description,$value,$pay_method){
        $this->payer=$payer;
        $this->country=$country;
        $this->currency=$currency;
        $this->reference_code=$reference_code;
        $this->description=$description;
        $this->value=$value;
        $this->pay_method=$pay_method;

        //Include the class
        include("libs/PayUPaymentTest.php");

        return new PayUPaymentTest(
            $this->varArray['account_id'],
            $this->varArray['installments_number'],
            $payer,
            $country,
            $currency,
            $reference_code,
            $description,
            $value,
            $pay_method
        );
    }

    public function getPayUPayment($payer_name,$country,$currency,$reference_code,$description,$value,$pay_method){
        $this->payer_name=$payer_name;
        $this->country=$country;
        $this->currency=$currency;
        $this->reference_code=$reference_code;
        $this->description=$description;
        $this->value=$value;
        $this->pay_method=$pay_method;

        //Include the class
        include("libs/PayUPayment.php");

        return new PayUPayment(
            $this->varArray['account_id'],
            $this->varArray['installments_number'],
            $payer_name,
            $country,
            $currency,
            $reference_code,
            $description,
            $value,
            $pay_method
        );
    }

    public function getPayuReportTest($report_type){
        $this->report_type=$report_type;

        //Include the class
        include("libs/PayUReportTest.php");

        return new PayUReportTest($report_type);
    }

    public function getPayuReport($report_type){
        $this->report_type=$report_type;

        //Include the class
        include("libs/PayUReport.php");

        return new PayUReport($report_type);
    }

}