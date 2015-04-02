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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class ServicesPayuPaymentController extends FosRestController
{


    public function transaction(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'name',
            'country',
            'currency',
            'reference_code',
            'description',
            'amount',
            'pay_method',
            'card_number',
            'expiration_date',
            'cvv',
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

            if($request->get($paramName)===''){
                throw new HttpException(400,"Missing value for '$paramName'");
            }

            $params[]=$request->get($paramName, 'null');
        }

        $count=count($paramNames);
        $paramsMongo=array();
        for($i=0; $i<$count; $i++){
            $paramsMongo[$paramNames[$i]]=$params[$i];
        }

        $paramsMongo['card_number']=substr_replace($paramsMongo['card_number'], '************', 0, -4);

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

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PayU')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Convertimos de cents a dos decimales
        $amount=$params[5]/100;

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('payu.service')->getPayUPaymentTest($params[0],$params[1],$params[2],$params[3],$params[4],$amount,$params[6])->transaction($params[7],$params[8],$params[9],$params[10],$params[11],$params[12]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('payu.service')->getPayUPayment($params[0],$params[1],$params[2],$params[3],$params[4],$amount,$params[6])->transaction($params[7],$params[8],$params[9],$params[10],$params[11],$params[12]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require');
        }

        //Response
        if(isset($datos['message'])){
            $transaction->setSuccessful(false);
            $rCode=400;
            $res="Bad request";

        }else{
            $transaction->setSuccessful(true);
            $rCode=201;
            $res="Reference created successfully";

        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(true);

        $dm->persist($transaction);
        $dm->flush();

        $datos['id_telepay']=$transaction->getId();

        $resp = new ApiResponseBuilder(
            $rCode,
            $res,
            $datos
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);


    }
    public function transactionTest(Request $request){
        $request->request->set('mode','T');
        return $this->transaction($request);

    }



    public function cash(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'name',
            'country',
            'currency',
            'reference_code',
            'description',
            'amount',
            'pay_method',
            'payer_dni'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }

            if($request->get($paramName)===''){
                throw new HttpException(400,"Missing value for '$paramName'");
            }

            $params[]=$request->get($paramName, 'null');
        }

        $count=count($paramNames);
        $paramsMongo=array();
        for($i=0; $i<$count; $i++){
            $paramsMongo[$paramNames[$i]]=$params[$i];
        }

        //Type user dni
        $paramsMongo['payer_dni']=substr_replace($paramsMongo['payer_dni'], '****', 0, -3);

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

        //Check Test mode
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Save the request in Mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PayU')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Convertimos de cents a dos decimales
        $amount=$params[5]/100;

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('payu.service')->getPayUPaymentTest($params[0],$params[1],$params[2],$params[3],$params[4],$amount,$params[6])->payment($params[7]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('payu.service')->getPayUPayment($params[0],$params[1],$params[2],$params[3],$params[4],$amount,$params[6])->payment($params[7]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require');
        }

        //Response
        if(isset($datos['error_code'])){
            $transaction->setSuccessful(false);
            $rCode=400;
            $res="Bad request";

        }else{
            $transaction->setSuccessful(true);
            $rCode=201;
            $res="Reference created successfully";

        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(true);

        $dm->persist($transaction);
        $dm->flush();

        $datos['id_telepay']=$transaction->getId();

        $resp = new ApiResponseBuilder(
            $rCode,
            $res,
            $datos
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function cashTest(Request $request){
        $request->request->set('mode','T');
        return $this->cash($request);
    }



    //TODO:esto esta mal
    public function report(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'report_type',
            'reference_code'
        );

        //Get the parameters sent by GET and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->query->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }

            if($request->get($paramName)===''){
                throw new HttpException(400,"Missing value for '$paramName'");
            }

            $params[]=$request->query->get($paramName, 'null');
        }

        $count=count($paramNames);
        $paramsMongo=array();
        for($i=0; $i<$count; $i++){
            $paramsMongo[$paramNames[$i]]=$params[$i];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PayU')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        if($params[0]=='order'){
            //Function report_by_order_id
            //Check if it's a Test or Production transaction
            if($mode=='T'){
                //Constructor in Test mode
                $datos=$this->get('payu.service')->getPayuReportTest($params[0])->report_by_order_id($params[1]);
                $datos=get_object_vars($datos);
            }elseif($mode=='P'){
                //Constructor in Production mode
                $datos=$this->get('payu.service')->getPayuReport($params[0])->report_by_order_id($params[1]);
                $datos=get_object_vars($datos);
            }else{
                //If is not one of the first shows an error message.
                throw new HttpException(400,'Wrong require');
            }
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
            if($mode=='T'){
                //Constructor in Test mode
                $datos=$this->get('payu.service')->getPayuReportTest($params[0])->report_by_reference($params[1]);
            }elseif($mode=='P'){
                //Constructor in Production mode
                $datos=$this->get('payu.service')->getPayuReport($params[0])->report_by_reference($params[1]);
            }else{
                //If is not one of the first shows an error message.
                throw new HttpException(400,'Wrong require');
            }
        }elseif($params[0]=='trans'){
            //Function report_by_transaction_id
            if($mode=='T'){
                //Constructor in Test mode
                $datos=$this->get('payu.service')->getPayuReportTest($params[0])->report_by_transaction_id($params[1]);
            }elseif($mode=='P'){
                //Constructor in Production mode
                $datos=$this->get('payu.service')->getPayuReport($params[0])->report_by_transactin_id($params[1]);
            }else{
                //If is not one of the first shows an error message.
                throw new HttpException(400,'Wrong require');
            }
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong report_type');
        }

        //die(print_r($datos,true));

        //Response
        if(isset($datos['error_code'])){
            $transaction->setSuccessful(false);
            $rCode=400;
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            $transaction->setSuccessful(true);
            $rCode=201;
            $datos['referenceCode']=substr($datos["referenceCode"],4);
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(true);

        $dm->persist($transaction);
        $dm->flush();

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function reportTest(Request $request){
        $request->request->set('mode','T');
        return $this->report($request);
    }



}