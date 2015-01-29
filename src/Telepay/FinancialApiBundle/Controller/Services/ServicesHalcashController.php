<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 01/08/14
 * Time: 10:55
 */

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class ServicesHalcashController extends FosRestController
{

    /**
     * This method allow send money to a phone with Halcash.
     *
     * @ApiDoc(
     *   section="Halcash",
     *   description="Send money to a phone.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   output={
     *
     *      },
     *   parameters={
     *      {
     *          "name"="phone_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Phone number to receive the money . E.g.:666000111"
     *      },
     *      {
     *          "name"="country",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Country. E.g.:MX, ES"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Amount in euros E.g: 100€=100."
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction description"
     *      },
     *       {
     *          "name"="pin",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Secure pin for the transaction with 4 digits. This number must be communicated to the receiver."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id."
     *      }
     *   }
     * )
     * @Rest\View
     */

    public function send(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'phone_number',
            'country',
            'amount',
            'reference',
            'pin',
            'transaction_id'
        );

        //Get the parameters sent by POST and put them in a $params array
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

        //die(print_r($params,true));

        $count=count($paramNames);
        $paramsMongo=array();
        for($i=0; $i<$count; $i++){
            $paramsMongo[$paramNames[$i]]=$params[$i];
        }

        //Concatenamos la referencia añadiendole el idusuario (0000)
        if($userid < 10){
            $params[5]='000'.$userid.$params[5];
        }elseif($userid<100){
            $params[5]='00'.$userid.$params[5];
        }elseif($userid<1000){
            $params[5]='0'.$userid.$params[5];
        }else{
            $params[5]=$userid.$params[5];
        }

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());

        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        if($params[1]==='MX'){

            //Adecuamos el telefono y ponemos el amount en centimos
            $params[2]=$params[2]*100;
            $params[0]='0005200'.$params[0];

            $transaction->setService($this->get('telepay.services')
                ->findByName('HalcashSend')->getId());
            $datos=$this->get('halcashsend.service')
                ->getHalcashSend($mode)
                ->send($params[0],$params[2],$params[3],$params[4],$params[5]);

            $datos=simplexml_load_string($datos);

            if(!$datos) throw new HttpException(502, "Empty response from halcash service");

            $datos=get_object_vars($datos);

            //die(print_r($datos,true));
            if(isset($datos['ATM_ALTCEMERR'])){
                $transaction->setSuccessful(false);
                $datos=get_object_vars($datos['ATM_ALTCEMERR']);
                $rCode=400;
                $res="Bad request";
            }elseif(isset($datos['ATM_ALTCEMRES'])){
                $transaction->setSuccessful(true);
                $transaction->setStatus('ISSUED');
                $datos=get_object_vars($datos['ATM_ALTCEMRES']);
                $rCode=201;
                $res="Reference created successfully";
            }else{
                $rCode=400;
                $res='Bad Request';
                $datos='Unexpected error';
            }
        }elseif($params[1]==='ES'){

            $transaction->setService($this->get('telepay.services')
                ->findByName('HalcashSend')->getId());
            $datos=$this->get('halcashsendsp.service')
                ->getHalcashSend($mode)
                ->send($params[0],$params[2],$params[3],$params[4],$params[5]);

            if($datos['errorcode']=='99'){
                $rCode=503;
                $res="Service temporally unavailable, maybe deposit account has no funds?";
            }elseif($datos['errorcode']=='0'){
                $transaction->setSuccessful(true);
                $transaction->setStatus('ISSUED');
                $rCode=201;
                $res="HalCash generated successfully";
            }else{
                $rCode=503;
                $res="Service Unavailable, unknown error";
            }


        }
        else throw new HttpException(400, "Bad country code, allowed ones are MX and ES");

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(true);

        $dm->persist($transaction);
        $dm->flush();
        $resp = new ApiResponseBuilder(
            $rCode,
            $res,
            $datos
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function sendTest(Request $request){
        $request->request->set('mode','T');
        return $this->send($request);
    }

    /**
     * This method allow send money to a phone with Halcash.
     *
     * @ApiDoc(
     *   section="Halcash",
     *   description="Send money to a phone.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   output={
     *
     *      },
     *   parameters={
     *      {
     *          "name"="phone_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Phone number to receive the money . E.g.:606152121."
     *      },
     *      {
     *          "name"="phone_prefix",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Phone prefix. E.g.: 34"
     *      },
     *      {
     *          "name"="country",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Country. E.g.:MX, ES"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Amount in cents E.g: 100€=10000."
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction description"
     *      },
     *       {
     *          "name"="pin",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Secure pin for the transaction with 4 digits. This number must be communicated to the receiver."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id."
     *      },
     *      {
     *          "name"="alias",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Alias assigned by Telepay"
     *      }
     *   }
     * )
     * @Rest\View
     */

    public function sendV2(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'phone_number',
            'country',
            'amount',
            'reference',
            'pin',
            'transaction_id',
            'phone_prefix',
            'alias'
        );

        //Get the parameters sent by POST and put them in a $params array
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

        //Concatenamos la referencia añadiendole el idusuario (0000)
        if($userid < 10){
            $params[5]='000'.$userid.$params[5];
        }elseif($userid<100){
            $params[5]='00'.$userid.$params[5];
        }elseif($userid<1000){
            $params[5]='0'.$userid.$params[5];
        }else{
            $params[5]=$userid.$params[5];
        }

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());

        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        if($params[1]==='MX'){
            $phone='0005200'.$params[0];
            $transaction->setService($this->get('telepay.services')
                ->findByName('HalcashSend')->getId());
            $datos=$this->get('halcashsend.service')
                ->getHalcashSend($mode)
                ->send($phone,$params[2],$params[3],$params[4],$params[5]);

            $datos=simplexml_load_string($datos);

            if(!$datos) throw new HttpException(502, "Empty response from halcash service");

            $datos=get_object_vars($datos);


            //die(print_r($datos,true));
            if(isset($datos['ATM_ALTCEMERR'])){
                $transaction->setSuccessful(false);
                $datos=get_object_vars($datos['ATM_ALTCEMERR']);
                $rCode=400;
                $res="Bad request";
            }elseif(isset($datos['ATM_ALTCEMRES'])){
                $transaction->setSuccessful(true);
                $transaction->setStatus('ISSUED');
                $datos=get_object_vars($datos['ATM_ALTCEMRES']);
                $rCode=201;
                $res="Reference created successfully";
            }else{
                $rCode=400;
                $res='Bad Request';
                $datos='Unexpected error';
            }
        }elseif($params[1]==='ES'){
            //arreglamos los centimos y el numero de telefono
            $params[2]=$params[2]/100;
            $params[6]=str_replace('+','',$params[6]);

            $transaction->setService($this->get('telepay.services')
                ->findByName('HalcashSend')->getId());
            $datos=$this->get('halcashsendsp.service')
                ->getHalcashSend($mode)
                ->sendV2($params[0],$params[6],$params[2],$params[3],$params[4],$params[5],$params[7]);

            if($datos['errorcode']=='99'){
                $rCode=503;
                $res="Service temporally unavailable, maybe deposit account has no funds?";
            }elseif($datos['errorcode']=='0'){
                $transaction->setSuccessful(true);
                $transaction->setStatus('ISSUED');
                $rCode=201;
                $res="HalCash generated successfully";
            }else{
                $rCode=503;
                $res="Service Unavailable, unknown error";
            }


        }
        else throw new HttpException(400, "Bad country code, allowed ones are MX and ES");

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

    public function sendTestV2(Request $request){
        $request->request->set('mode','T');
        return $this->sendV2($request);
    }

    /**
     * This method allow payments with Halcash.
     *
     * @ApiDoc(
     *   section="Halcash",
     *   description="Do a payment with Halcash",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="phone_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Phone number to receive the money with 17 digits. E.g.:00034000606152121"
     *      },
     *      {
     *          "name"="country",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Country. E.g.:MX, ES"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Amount in cents E.g: 100€=10000."
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction description"
     *      },
     *       {
     *          "name"="pin",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Secure pin for the transaction with 4 digits. This number must be communicated to the receiver."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id."
     *      }
     *   }
     * )
     *
     * @Rest\View
     */

    public function payment(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'phone_number',
            'country',
            'amount',
            'reference',
            'pin',
            'transaction_id'
        );

        //Get the parameters sent by POST and put them in a $params array
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

        //Concatenamos la referencia añadiendole el idusuario (0000)
        /*if($userid < 10){
            $params[1]='000'.$userid.$params[1];
        }elseif($userid<100){
            $params[1]='00'.$userid.$params[1];
        }elseif($userid<1000){
            $params[1]='0'.$userid.$params[1];
        }else{
            $params[1]=$userid.$params[1];
        }*/

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());

        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        if($params[1]==='MX') {
            $transaction->setService($this->get('telepay.services')
                ->findByName('HalcashPayment')->getId());
            $datos = $this->get('halcashpayment.service')
                ->getHalcashPayment($mode)
                ->payment($params[0], $params[2], $params[3], $params[4], $params[5]);

        }elseif($params[1]==='ES'){
            throw new HttpException(503,'Service unavailable');
            //$transaction->setService($this->get('telepay.services')->findByName('HalcashSend')->getId());
            //$datos=$this->get('halcash.service')->getHalcashSP($mode)-> request($params[0],$params[2],$params[3],$params[4],$params[5]);
        }
        else throw new HttpException(400, "Bad country code");

        $datos=simplexml_load_string($datos);

        if(!$datos) throw new HttpException(502, "Empty response from halcash service");

        $datos=get_object_vars($datos);

        if(isset($datos['ATM_AUTCADERR'])){
            $transaction->setSuccessful(false);
            $datos=get_object_vars($datos['ATM_AUTCADERR']);
            $rCode=400;
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }elseif(isset($datos['ATM_AUTCADRES'])){
            $transaction->setSuccessful(true);
            $datos=get_object_vars($datos['ATM_AUTCADRES']);
            $rCode=201;
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }else{
            throw new HttpException(502, "Bad service response");
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

    public function paymentTest(Request $request){
        $request->request->set('mode','T');
        return $this->payment($request);
    }

    /**
     * This method allow cancel Halcash tickets.
     *
     * @ApiDoc(
     *   section="Halcash",
     *   description="Cancel Halcash tickets.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   output={
     *
     *      },
     *   parameters={
     *      {
     *          "name"="country",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Country. E.g.:MX, ES"
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Cancel description"
     *      },
     *       {
     *          "name"="id_telepay",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ticket number to cancel."
     *      }
     *   }
     * )
     * @Rest\View
     */

    public function cancel(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'country',
            'reference',
            'id_telepay'
        );

        //Get the parameters sent by POST and put them in a $params array
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

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        if($mode=='T'){
            throw new HttpException(503,'Test service unavailable');
        }

        //Constructor
        if($params[0]==='MX'){

            throw new HttpException(503,"Service Unavailable");

        }elseif($params[0]==='ES'){

            $dm = $this->get('doctrine_mongodb')->getManager();
            $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                ->find($params[2]);
            $ticket=$transaction->getReceivedData();
            $ticket=json_decode($ticket);
            $ticket=$ticket->halcashticket;
            $reference=$params[1];

            $transaction->setService($this->get('telepay.services')
                ->findByName('HalcashSend')->getId());
            $datos=$this->get('halcashsendsp.service')
                ->getHalcashSend($mode)
                ->cancelation($ticket,$reference);

            if($datos['errorcode']=='99'){
                $rCode=503;
                $res="Service temporally unavailable, maybe deposit account has no funds?";
            }elseif($datos['errorcode']=='0'){
                $transaction->setStatus('CANCELLED');
                $rCode=201;
                $res="HalCash generated successfully";
            }else{
                $rCode=503;
                $res="Service Unavailable, unknown error";
            }

        }
        else throw new HttpException(400, "Bad country code, allowed ones are MX and ES");

        //Guardamos la respuesta
        $dm->persist($transaction);
        $dm->flush();
        $resp = new ApiResponseBuilder(
            $rCode,
            $res,
            $datos
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function cancelTest(Request $request){
        $request->request->set('mode','T');
        return $this->cancel($request);
    }



}