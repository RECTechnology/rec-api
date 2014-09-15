<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 14/08/14
 * Time: 09:55
 */
namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class Services2000chargePagofacilController extends FOSRestController
{

    public function transaction(Request $request)
    {

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $name='Juan';
        $surname='Lopez Hernandez';
        $cp='40500';
        $mail='mail@mail.com';
        $phone='676543423';
        $mobile_phone='654346545';
        $street_number='calle';
        $colony='Polanco';
        $city='Miguel Hidalgo';
        $quarter='Distrito Federal';
        $country='Mexico';
        $transaction_id='555';


        static $paramNames = array(
            'card_number',
            'cvt',
            'expiration_month',
            'expiration_year',
            'amount',
            'user_id',
            'password'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Comprobacion user y password
        if(($params[5]!='854729')||($params[6]!='1117873')){
            throw new HttpException(401,"Unauthorized");
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';
        //var_dump($mode);

        $paramsMongo=$params;
        $paramsMongo[0]=substr_replace($paramsMongo[0], '************', 0, -4);

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PagoFacil')->getId());
        $transaction->setUser(4); //2000charge user at the API
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('pagofacil.service')->getPagofacilTest()->request($name,$surname,$params[0],$params[1],$cp,$params[2],$params[3],$params[4],$mail,$phone,$mobile_phone,$street_number,$colony,$city,$quarter,$country,$transaction_id);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('pagofacil.service')->getPagofacil()->request($name,$surname,$params[0],$params[1],$cp,$params[2],$params[3],$params[4],$mail,$phone,$mobile_phone,$street_number,$colony,$city,$quarter,$country,$transaction_id);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Response
        if(isset($datos['error'])){
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
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
        $transaction->setSuccessful(true);
        $dm->persist($transaction);
        $dm->flush();

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }
    public function transactionTest(Request $request){
        $request->request->set('mode','T');
        return $this->transaction($request);
    }



    public function status(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = 1;

        static $paramNames = array(
            'transaction_id',
            'user_id',
            'password'
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
            $params[0]='000'.$userid.$params[0];
        }elseif($userid<100){
            $params[0]='00'.$userid.$params[0];
        }elseif($userid<1000){
            $params[0]='0'.$userid.$params[0];
        }else{
            $params[0]=$userid.$params[0];
        }
        //var_dump($params[0]);

        //Comprobacion user y password
        if(($params[1]!='854729')||($params[2]!='1117873')){
            throw new HttpException(401,"Unauthorized");
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PagoFacil')->getId());
        $transaction->setUser(1);
        $transaction->setSentData(json_encode($params));
        $transaction->setMode($mode === 'P');

        //Include the class
        include("../vendor/pagofacil/PagofacilService.php");

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
        if(isset($datos['error_code'])){
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
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
        $transaction->setSuccessful(true);
        $dm->persist($transaction);
        $dm->flush();


        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }
    public function statusTest(Request $request){
        $request->request->set('mode','T');
        return $this->status($request);
    }
}