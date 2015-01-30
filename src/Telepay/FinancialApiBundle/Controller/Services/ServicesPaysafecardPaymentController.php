<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 12/08/14
 * Time: 15:23
 */

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;


class ServicesPaysafecardPaymentController extends FOSRestController
{


    /**
     * This method redirect clients to the Paysafecard getaway to finish the payment.
     *
     * @ApiDoc(
     *   section="Paysafecard",
     *   description="Returns the redirect info.",
     *   https="true",
     *   output="Telepay\FinancialApiBundle\Controller\Services",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="mtid",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyyThh:mm:ss",
     *          "description"="Unique Transation id."
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Currency code. ISO-4217. Ex: MXN"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Amount for the transaction. Ex: 100.00"
     *      },
     *      {
     *          "name"="url_success",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url if the transaction was completed succesfully."
     *      },
     *      {
     *          "name"="url_fail",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url if the transaction was an error."
     *      },
     *      {
     *          "name"="url_notification",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="The url where we notify the success transactions."
     *      },
     *      {
     *          "name"="merchant_client_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="The merchant client from Telepay."
     *      }
     *   }
     * )
     *
     */

    public function request(Request $request){

        static $paramNames = array(
            'mtid',
            'currency',
            'amount',
            'url_success',
            'url_fail',
            'url_notification',
            'merchant_client_id'
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

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('SafetyPay')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        $dms = $this->get('doctrine_mongodb')->getManager();
        $dms->persist($transaction);
        $id=$transaction->getId();

        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_notification=$url_base.'/notifications/v1/paysafecard';
        //Check if it's a Test or Production transaction
        $datos=$this->get('paysafecard.service')->getPaysafecard()-> request($mode,$params[0],$params[1],$params[2],$params[3],$params[4],$url_notification,$params[6]);

        //Response
        if($datos['error_number']==0){
            $transaction->setSuccessful(true);
            $rCode=201;
            $res="Reference created successfully";

        }else{
            $transaction->setSuccessful(false);
            $rCode=400;
            $res="Bad request";

        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(false);

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

    public function requestTest(Request $request){
        $request->request->set('mode','T');
        return $this->request($request);
    }

    /**
     * This method redirect clients to the Paysafecard getaway to finish the payment.
     *
     * @ApiDoc(
     *   section="Paysafecard",
     *   description="Returns the redirect info.",
     *   https="true",
     *   output="Telepay\FinancialApiBundle\Controller\Services",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="mtid",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyyThh:mm:ss",
     *          "description"="Unique Transation id."
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Currency code. ISO-4217. Ex: MXN"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Amount for the transaction. Eg: 100.00 = 10000."
     *      },
     *      {
     *          "name"="url_success",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url if the transaction was completed succesfully."
     *      },
     *      {
     *          "name"="url_fail",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url if the transaction was an error."
     *      },
     *      {
     *          "name"="url_notification",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="The url where we notify the success transactions."
     *      },
     *      {
     *          "name"="merchant_client_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="The merchant client from Telepay."
     *      }
     *   }
     * )
     *
     */

    public function requestV2(Request $request){

        static $paramNames = array(
            'mtid',
            'currency',
            'amount',
            'url_success',
            'url_fail',
            'url_notification',
            'merchant_client_id'
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

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('SafetyPay')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        $dms = $this->get('doctrine_mongodb')->getManager();
        $dms->persist($transaction);
        $id=$transaction->getId();

        //Convertimos de cents a dos decimales
        $amount=$params[2]/100;

        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_notification=$url_base.'/notifications/v1/paysafecard';

        //Check if it's a Test or Production transaction
        $datos=$this->get('paysafecard.service')->getPaysafecard()-> request($mode,$params[0],$params[1],$amount,$params[3],$params[4],$url_notification,$params[6]);

        //Response
        if($datos['error_number']==0){
            $transaction->setSuccessful(true);
            $rCode=201;
            $res="Reference created successfully";

        }else{
            $transaction->setSuccessful(false);
            $rCode=400;
            $res="Bad request";

        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(false);

        $dm->persist($transaction);
        $dm->flush();

        $datos['id_telepay']=$id;

        $resp = new ApiResponseBuilder(
            $rCode,
            $res,
            $datos
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);
    }

    public function requestTestV2(Request $request){
        $request->request->set('mode','T');
        return $this->requestV2($request);
    }

}