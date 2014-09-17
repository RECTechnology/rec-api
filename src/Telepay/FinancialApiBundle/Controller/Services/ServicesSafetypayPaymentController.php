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


class ServicesSafetypayPaymentController extends FOSRestController
{


    /**
     * This method redirect clients to the SafetyPay getaway to finish the payment.
     *
     * @ApiDoc(
     *   section="SafetyPay payments",
     *   description="Returns the redirection info.",
     *   https="true",
     *   output="Telepay\FinancialApiBundle\Controller\Services",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="date_time",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyyThh:mm:ss",
     *          "description"="Transaction Date."
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
     *      }
     *   }
     * )
     *
     */

    public function request(Request $request){

        static $paramNames = array(
            'date_time',
            'currency',
            'amount',
            'url_success',
            'url_fail'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
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
        $transaction->setSentData(json_encode($params));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('safetypay.service')->getSafetypayTest()-> request($params[0],$params[1],$params[2],$params[3],$params[4]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('safetypay.service')->getSafetypay()-> request($params[0],$params[1],$params[2],$params[3],$params[4]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Response
        if($datos['error_number']==0){
            $transaction->setSuccessful(true);
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }else{
            $transaction->setSuccessful(false);
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
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

}