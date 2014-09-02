<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 19/08/14
 * Time: 17:07
 */

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use PayUPayment;
use PayUPaymentTest;
use PayUReport;
use PayUReportTest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServicesPayuPaymentController extends FosRestController
{
    private $varArray =array(
        'installments_number'   =>  '3',
        'account_id'            =>  '500547'
    );

    /**
     * This method allows client to do a credit card payment.
     *
     * @ApiDoc(
     *   section="PayU Payment",
     *   description="Returns a JSON with the payment response.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="payer_name",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Credit Card Name."
     *      },
     *      {
     *          "name"="country",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Payer Country."
     *      },
     *      {
     *          "name"="reference_code",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Reference payment code."
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Currency ISO...."
     *      },
     *      {
     *          "name"="description",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction description."
     *      },
     *      {
     *          "name"="value",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction amount. Ex: 100.00"
     *      },
     *      {
     *          "name"="card_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Credit card number."
     *      },
     *      {
     *          "name"="expiration_date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="YYYY/MM",
     *          "description"="Expiration date for the credit card. Ex: 2015/01"
     *      },
     *      {
     *          "name"="without_cvv2",
     *          "dataType"="boolean",
     *          "required"="true",
     *          "description"="true if cvv is not needed or false if it's needed"
     *      },
     *      {
     *          "name"="card_security_code",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="3 digits at the back of the credit card"
     *      },
     *      {
     *          "name"="payment_method",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Payment method as MASTERCARD,VISA..."
     *      },
     *      {
     *          "name"="tax_base",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Pretax Base."
     *      },
     *      {
     *          "name"="tax_value",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Tax value. Ex: 16.00"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function transaction(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'payer_name',
            'country',
            'currency',
            'reference_code',
            'description',
            'value',
            'pay_method',
            'card_number',
            'expiration_date',
            'security_code',
            'without_cvv2',
            'tax_base',
            'tax_value'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Concatenamos la referencia añadiendole el idusuario (0000)
        if($userid < 10){
            $params[3]='000'.$userid.$params[3];
        }elseif($userid<100){
            $params[3]='00'.$userid.$params[3];
        }elseif($userid<1000){
            $params[3]='0'.$userid.$params[3];
        }else{
            $params[3]=$userid.$params[3];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Include the class Test
            include("../vendor/payu/PayUPaymentTest.php");
            //Constructor in Test mode
            $constructor=new PayUPaymentTest($this->varArray['account_id'],$this->varArray['installments_number'],$params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6]);
        }elseif($mode=='P'){
            //Include the class Prod
            include("../vendor/payu/PayUPayment.php");
            //Constructor in Production mode
            $constructor=new PayUPayment($this->varArray['account_id'],$this->varArray['installments_number'],$params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6]);

        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require');
        }

        //Function Info
        $datos=$constructor -> transaction($params[7],$params[8],$params[9],$params[10],$params[11],$params[12]);

        //Response
        /*if(isset($datos['error_code'])){
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{*/
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        //}

        $view = $this->view($resp, 201);

        return $this->handleView($view);


    }
    public function transactionTest(Request $request){
        $request->request->set('mode','T');
        return $this->transaction($request);

    }

    /**
     * This method allows client to do a credit card payment.
     *
     * @ApiDoc(
     *   section="PayU Payment",
     *   description="Returns a JSON with the payment information.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="payer_name",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Customer Name."
     *      },
     *      {
     *          "name"="country",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Payer Country."
     *      },
     *      {
     *          "name"="reference_code",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Reference payment code."
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Currency ISO...."
     *      },
     *      {
     *          "name"="description",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction description."
     *      },
     *      {
     *          "name"="value",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction amount. Ex: 100.00"
     *      },
     *      {
     *          "name"="payment_method",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Payment method as MASTERCARD,VISA..."
     *      },
     *      {
     *          "name"="payer_dni",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Customer Dni."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function cash(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'payer_name',
            'country',
            'currency',
            'reference_code',
            'description',
            'value',
            'pay_method',
            'payer_dni'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Concatenamos la referencia añadiendole el idusuario (0000)
        if($userid < 10){
            $params[3]='000'.$userid.$params[3];
        }elseif($userid<100){
            $params[3]='00'.$userid.$params[3];
        }elseif($userid<1000){
            $params[3]='0'.$userid.$params[3];
        }else{
            $params[3]=$userid.$params[3];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';
        //var_dump($mode);

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Include the class
            include("../vendor/payu/PayUPaymentTest.php");
            //Constructor in Test mode
            $constructor=new PayUPaymentTest($this->varArray['account_id'],$this->varArray['installments_number'],$params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6]);
        }elseif($mode=='P'){
            //Include the class
            include("../vendor/payu/PayUPayment.php");
            //Constructor in Production mode
            $constructor=new PayUPayment($this->varArray['account_id'],$this->varArray['installments_number'],$params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require');
        }

        //Function Info
        $datos=$constructor -> payment($params[7]);

        //Response
        /*if(isset($datos['error_code'])){
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{*/
        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );
        //}

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }

    public function cashTest(Request $request){
        $request->request->set('mode','T');
        return $this->cash($request);
    }

    /**
     * This method allows client to do a credit card payment.
     *
     * @ApiDoc(
     *   section="PayU Payment",
     *   description="Returns a JSON with the payment information.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="report_type",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Report_type can be order,ref or trans."
     *      },
     *      {
     *          "name"="code",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Code to report. (order_id,reference_code,transaction_id"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function report(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'report_type',
            'reference_code'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }



        //var_dump($params[1]);

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Include the class
            include("../vendor/payu/PayUReportTest.php");
            //Constructor in Test mode
            $constructor=new PayUReportTest($params[0]);
        }elseif($mode=='P'){
            //Include the class
            include("../vendor/payu/PayUReport.php");
            //Constructor in Production mode
            $constructor=new PayUReport($params[0]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require');
        }

        if($params[0]=='order'){
            //Function report_by_order_id
            $datos=$constructor -> report_by_order_id($params[1]);
        }elseif($params[0]='ref'){
            //Concatenamos la referencia añadiendole el idusuario (0000)
            if($userid < 10){
                $params[1]='000'.$userid.$params[1];
            }elseif($userid<100){
                $params[1]='00'.$userid.$params[1];
            }elseif($userid<1000){
                $params[1]='0'.$userid.$params[1];
            }else{
                $params[1]=$userid.$params[1];
            }
            //Function report_by_reference
            $datos=$constructor -> report_by_reference($params[1]);
        }elseif($params[0]=='trans'){
            //Function report_by_transaction_id
            $datos=$constructor -> report_by_transaction_id($params[1]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong report_type');
        }

        //Response
        if(isset($datos['error_code'])){
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            $datos['referenceCode']=substr($datos["referenceCode"],4);
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }

    public function reportTest(Request $request){
        $request->request->set('mode','T');
        return $this->report($request);
    }



}