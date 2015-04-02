<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 14/08/14
 * Time: 09:55
 */
namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class ServicesPagofacilPaymentController extends RestApiController
{



    public function transaction(Request $request)
    {

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'name',
            'surname',
            'card_number',
            'cvv',
            'cp',
            'expiration_month',
            'expiration_year',
            'amount',
            'mail',
            'phone',
            'mobile_phone',
            'street_number',
            'colony',
            'city',
            'quarter',
            'country',
            'transaction_id'
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
            $params[16]='000'.$userid.$params[16];
        }elseif($userid<100){
            $params[16]='00'.$userid.$params[16];
        }elseif($userid<1000){
            $params[16]='0'.$userid.$params[16];
        }else{
            $params[16]=$userid.$params[16];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PagoFacil')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('pagofacil.service')->getPagofacilTest()->request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8],$params[9],$params[10],$params[11],$params[12],$params[13],$params[14],$params[15],$params[16]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('pagofacil.service')->getPagofacil()->request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8],$params[9],$params[10],$params[11],$params[12],$params[13],$params[14],$params[15],$params[16]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Response
        if(isset($datos['error'])){
            $transaction->setSuccessful(false);
            $rCode=400;
            $res="Bad request";
        }else{
            $transaction->setSuccessful(true);
            $rCode=201;
            $res= "Reference created successfully";
        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(true);

        $dm->persist($transaction);
        $dm->flush();

        $datos['id_telepay']=$transaction->getId();

        $respView = $this->buildRestView(
            $rCode,
            $res,
            $datos
        );

        return $this->handleView($respView);

    }
    public function transactionTest(Request $request){
        $request->request->set('mode','T');
        return $this->transaction($request);
    }



    public function transactionV2(Request $request)
    {

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'name',
            'surname',
            'card_number',
            'cvv',
            'cp',
            'expiration_month',
            'expiration_year',
            'amount',
            'email',
            'phone',
            'mobile_phone',
            'street_number',
            'colony',
            'city',
            'quarter',
            'country',
            'transaction_id'
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
            $params[16]='000'.$userid.$params[16];
        }elseif($userid<100){
            $params[16]='00'.$userid.$params[16];
        }elseif($userid<1000){
            $params[16]='0'.$userid.$params[16];
        }else{
            $params[16]=$userid.$params[16];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PagoFacil')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Convertimos de centimos a dos decimales el amount
        $amount=$params[7]/100;

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('pagofacil.service')->getPagofacilTest()->request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$amount,$params[8],$params[9],$params[10],$params[11],$params[12],$params[13],$params[14],$params[15],$params[16]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('pagofacil.service')->getPagofacil()->request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$amount,$params[8],$params[9],$params[10],$params[11],$params[12],$params[13],$params[14],$params[15],$params[16]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Response
        if(isset($datos['error'])){
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

        $respView = $this->buildRestView(
            $rCode,
            $res,
            $datos
        );

        return $this->handleView($respView);

    }
    public function transactionTestV2(Request $request){
        $request->request->set('mode','T');
        return $this->transactionV2($request);
    }



    //TODO:este status esta mal
    public function status(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'transaction_id'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->query ->has($paramName)){
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
        $transaction->setService($this->get('telepay.services')->findByName('PagoFacil')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('pagofacil.service')->getPagofacilTest()->status($params[0]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('pagofacil.service')->getPagofacil()->status($params[0]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Response
        if(isset($datos['WebServices_Transacciones']['verificar']['error'])){
            unset ($datos['WebServices_Transacciones']['verificar']['data']);
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
    public function statusTest(Request $request){
        $request->request->set('mode','T');
        return $this->status($request);
    }
}