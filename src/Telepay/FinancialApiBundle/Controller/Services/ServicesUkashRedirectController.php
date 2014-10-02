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

class ServicesUkashRedirectController extends FosRestController
{

    /**
     * This method redirect to Ukash site for finish the payment.
     *
     * @ApiDoc(
     *   section="Ukash",
     *   description="Returns a JSON with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   output={
     *
     *      },
     *   parameters={
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction amount. Not allowed decimal values"
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction ID"
     *      },
     *      {
     *          "name"="consumer_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Consumer ID"
     *      },
     *       {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction currency ISO-4217"
     *      },
     *      {
     *          "name"="url_succes",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="URL if the process has gone fine."
     *      },
     *      {
     *          "name"="url_fail",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="URL if the process has failed."
     *      },
     *      {
     *          "name"="url_notification",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="URL for notification de payment"
     *      }
     *   }
     * )
     *
     */

    public function request(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'amount',
            'transaction_id',
            'consumer_id',
            'currency',
            'url_succes',
            'url_fail',
            'url_notification'
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
            $params[1]='000'.$userid.$params[1];
        }elseif($userid<100){
            $params[1]='00'.$userid.$params[1];
        }elseif($userid<1000){
            $params[1]='0'.$userid.$params[1];
        }else{
            $params[1]=$userid.$params[1];
        }

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Ukash')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        $datos=$this->get('ukash.service')->getUkash($mode)-> request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6]);

        if($datos['error_number']!=0){
            $transaction->setSuccessful(false);
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            $transaction->setSuccessful(true);
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

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }

    public function requestTest(Request $request){
        $request->request->set('mode','T');
        return $this->request($request);
    }

    /**
     * This method obtain a payment status.
     *
     * @ApiDoc(
     *   section="Ukash",
     *   description="Returns a JSON with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="utid",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id from the payment we want consult."
     *      }
     *   }
     * )
     *
     */

    public function status(Request $request){

        static $paramNames = array(
            'utid'
        );

        //Get the parameters sent by GET and put them in a $params array

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
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Ukash')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        $datos=$this->get('ukash.service')->getUkash($mode)-> status($params[0]);

        if (isset($datos['error_code'])){
            $transaction->setSuccessful(false);
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            $transaction->setSuccessful(true);
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


        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }

    public function statusTest(Request $request){
        $request->request->set('mode','T');
        return $this->status($request);
    }
}