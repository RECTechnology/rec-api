<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 01/10/14
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

class ServicesUkashGenerateController extends FosRestController
{

    /**
     * This method returns a code to obtain the barcode.
     *
     * @ApiDoc(
     *   section="Ukash Generate",
     *   description="Returns a JSON with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="merchant_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Merchant Id"
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This parameter is always MXN"
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Shopping amount"
     *      }
     *   }
     * )
     *
     */

    public function request(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'merchant_id',
            'currency',
            'transaction_id',
            'amount'
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
            $params[2]='000'.$userid.$params[2];
        }elseif($userid<100){
            $params[2]='00'.$userid.$params[2];
        }elseif($userid<1000){
            $params[2]='0'.$userid.$params[2];
        }else{
            $params[2]=$userid.$params[2];
        }

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        if($mode=='T'){
            throw new HttpException(503,"Service unavailable");
        }

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Ukashgenerate')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        $datos=$this->get('ukashgenerate.service')->getUkashOnline()-> request($params[0],$params[1],$params[2],$params[3]);

        $datos['transactionId'] = substr($datos['transactionId'], 4);

        if($datos['txCode']!=0){
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
     * This method allows to expend ukash voucher's.
     *
     * @ApiDoc(
     *   section="Ukash Redemption",
     *   description="Returns a JSON with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="merchant_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Merchant Id"
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This parameter is always MXN"
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id"
     *      },
     *      {
     *          "name"="voucher_value",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ukash voucher value"
     *      },
     *      {
     *          "name"="voucher_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ukash voucher number. 19 digits."
     *      },
     *      {
     *          "name"="transaction_amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Purchase amount"
     *      }
     *   }
     * )
     *
     */

    public function redemption(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'merchant_id',
            'currency',
            'transaction_id',
            'voucher_value',
            'voucher_number',
            'transaction_amount'
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
            $params[2]='000'.$userid.$params[2];
        }elseif($userid<100){
            $params[2]='00'.$userid.$params[2];
        }elseif($userid<1000){
            $params[2]='0'.$userid.$params[2];
        }else{
            $params[2]=$userid.$params[2];
        }

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        if($mode=='T'){
            throw new HttpException(503,"Service unavailable");
        }

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Ukashredemption')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        $datos=$this->get('ukashredemption.service')->getUkashOnline()-> redemption($params[0],$params[1],$params[2],$params[3],$params[4],$params[5]);

        $datos['transactionId'] = substr($datos['transactionId'], 4);

        if($datos['txCode']!=0){
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

    public function redemptionTest(Request $request){
        $request->request->set('mode','T');
        return $this->redemption($request);
    }

    /**
     * This method allows to know the ukash redemption status.
     *
     * @ApiDoc(
     *   section="Ukash Redemption",
     *   description="Returns a JSON with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id"
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This parameter is always MXN"
     *      },
     *
     *      {
     *          "name"="voucher_value",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ukash voucher value"
     *      },
     *      {
     *          "name"="voucher_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ukash voucher number. 19 digits."
     *      },
     *      {
     *          "name"="transaction_amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Purchase amount"
     *      }
     *   }
     * )
     *
     */

    public function status(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'transaction_id',
            'currency',
            'voucher_value',
            'voucher_number',
            'transaction_amount'
        );

        //Get the parameters sent by POST and put them in a $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->query ->has($paramName)){
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
            $params[0]='000'.$userid.$params[0];
        }elseif($userid<100){
            $params[0]='00'.$userid.$params[0];
        }elseif($userid<1000){
            $params[0]='0'.$userid.$params[0];
        }else{
            $params[0]=$userid.$params[0];
        }

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        if($mode=='T'){
            throw new HttpException(503,"Service unavailable");
        }

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Ukashredemption')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Constructor
        $datos=$this->get('ukashredemption.service')->getUkashOnline()-> status($params[0],$params[1],$params[2],$params[3],$params[4]);

        $datos['transactionId'] = substr($datos['transactionId'], 4);

        if($datos['txCode']!=0){
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