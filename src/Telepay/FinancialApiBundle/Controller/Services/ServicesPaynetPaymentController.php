<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 24/07/14
 * Time: 08:54
 */
namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class ServicesPaynetPaymentController extends FosRestController
{

    /**
     * Register a transaction in the Paynet system. It's mandatory do the Ejecuta method to finish the payment.
     *
     * @ApiDoc(
     *   section="Paynet Payment for Services",
     *   description="Register a transaction in the Paynet system.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyy",
     *          "description"="Transaction Date."
     *      },
     *      {
     *          "name"="hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction Hour."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This id must be unique along the day."
     *      },
     *      {
     *          "name"="sku",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a sku reference for the payment. Every service has a unique sku reference."
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a reference for the payment. Every service has a unique reference."
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Only for the phone recharge. If not used=0"
     *      }
     *   }
     * )
     *
     */

    public function info(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'date',
            'hour',
            'transaction_id',
            'sku',
            'reference',
            'amount'
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
        //var_dump($params[2]);

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PaynetPayment')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('paynetpay.service')->getPaynetPayTest()-> info($params[0],$params[1],$params[2],$params[3],$params[4],$params[5]);
            //die(print_r($datos,true));
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('paynetpay.service')->getPaynetPay()->info($params[0],$params[1],$params[2],$params[3],$params[4],$params[5]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require->Test with T or P');
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
        $transaction->setCompleted(false);

        $dm->persist($transaction);
        $dm->flush();

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function infoTest(Request $request){
        $request->request->set('mode','T');
        return $this->info($request);
    }
    /**
     * Confirm the payment in the Paynet system. It's mandatory do the Info method first to register the transaction.
     *
     * @ApiDoc(
     *   section="Paynet Payment for Services",
     *   description="This method allows client finish the service payment.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyy",
     *          "description"="Transaction Date."
     *      },
     *      {
     *          "name"="hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction Hour."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This id must be the same that the info method."
     *      },
     *      {
     *          "name"="sku",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a sku reference for the payment. Every service has a unique sku reference."
     *      },
     *      {
     *          "name"="fee",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This comission value is obtained in the info method response"
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This reference is returned in the info method array."
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Amount value must be the same value that appears in the table 1.x."
     *      },
     *      {
     *          "name"="dv",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Verification digits -> If is not needed we should write 0"
     *      },
     *      {
     *          "name"="token",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Token string -> If is not needed we should write 0"
     *      }
     *   }
     * )
     *
     */

    public function ejecuta(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'date',
            'hour',
            'transaction_id',
            'sku',
            'fee',
            'reference',
            'amount',
            'dv',
            'token'
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
        //var_dump($params[2]);

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PaynetPayment')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('paynetpay.service')->getPaynetPayTest()-> ejecuta($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('paynetpay.service')->getPaynetPay()->ejecuta($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require->Test with T or P');
        }

        //Response
        if(isset($datos['error_code'])){
            $transaction->setSuccessful(false);
            $transaction->setCompleted(false);

            $rCode=400;
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            //die(print_r($datos,true));
            $transaction->setSuccessful(true);
            $transaction->setCompleted(true);

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

        $dm->persist($transaction);
        $dm->flush();

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function ejecutaTest(Request $request){
        $request->request->set('mode','T');
        return $this->ejecuta($request);
    }

    /**
     * This method allows client to obtain reverse for a payment.
     *
     * @ApiDoc(
     *   section="Paynet Payment for Services",
     *   description="Consult a transaction done with Paynet.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyy",
     *          "description"="Transaction Date."
     *      },
     *      {
     *          "name"="hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction Hour."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This id must be the same that the ejecuta method."
     *      },
     *      {
     *          "name"="sku",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a sku reference for the payment. Every service has a unique sku reference."
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a reference for the payment. Every service has a unique reference."
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Amount value must be the same value that appears in the table 1.x."
     *      }
     *   }
     * )
     *
     */

    public function reversa(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'date',
            'hour',
            'transaction_id',
            'sku',
            'reference',
            'amount'
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
        //var_dump($params[2]);

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset ($mode)) $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PaynetPayment')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('paynetpay.service')->getPaynetPayTest()-> reversa($params[0],$params[1],$params[2],$params[3],$params[4],$params[5]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('paynetpay.service')->getPaynetPay()-> reversa($params[0],$params[1],$params[2],$params[3],$params[4],$params[5]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require->Test with T or P');
        }

        //response
        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

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

    public function reversaTest(Request $request){
        $request->request->set('mode','T');
        return $this->reversa($request);
    }
}