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
use SafetyPayment;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;


class ServicesSafetypayPaymentController extends FOSRestController
{
    //This parameters are unique for us. Don't give to the client
    //For Test
    private $testArray =array(
        'api_key'           =>  '247acc3167b49419634fe3b87e8623ef',
        'signature_key'     =>  '43b4da81e4a4fc2a1c1f1b45d53bf577',
        'merchant_reference'=>  '5339',
        'language'          =>  'ES',
        'tracking_code'     =>  '',
        'expiration_time'   =>  '5',
        'response_format'   =>  'CSV',
        'url_safety'        =>  'https://mws2.safetypay.com/Sandbox/express/post/v.2.2/CreateExpressToken.aspx'
    );

    //For production
    private $prodArray =array(
        'api_key'           =>  '240f67fb0bf4689a27be8ce1e76af109',
        'signature_key'     =>  'bbe1cb9502d4a8fae6bcfa08014e0433f4b1445e2a9b871f9e78f88d97',
        'merchant_reference'=>  '5339',
        'language'          =>  'ES',
        'tracking_code'     =>  '',
        'expiration_time'   =>  '5',
        'response_format'   =>  'CSV',
        'url_safety'        =>  'https://mws2.safetypay.com/express/post/v.2.2/CreateExpressToken.aspx'
    );

    /**
     * This method allows client to obtain info for the payment services.
     *
     * @ApiDoc(
     *   section="SafetyPay payments",
     *   description="Returns a JSON with the Info for the payment.",
     *   https="true",
     *   output="Telepay\FinancialApiBundle\Controller\Services",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="request_date_time",
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
     *          "name"="url_error",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url if the transaction was an error."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function request(Request $request){

        static $paramNames = array(
            'request_date_time',
            'currency',
            'amount',
            'url_success',
            'url_error'
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
        $transaction->setService($this->get('telepay.services')->findByName('Safetypay')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($params));
        $transaction->setMode($mode === 'P');


        //Include the class
        include("../vendor/safetypay/SafetyPayment.php");

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $constructor=new SafetyPayment($this->testArray['api_key'],$this->testArray['signature_key'],$this->testArray['merchant_reference'],$this->testArray['language'],$this->testArray['tracking_code'],$this->testArray['expiration_time'],$this->testArray['response_format'],$this->testArray['url_safety'],$this->testArray['signature_key']);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $constructor=new SafetyPayment($this->prodArray['api_key'],$this->prodArray['signature_key'],$this->prodArray['merchant_reference'],$this->prodArray['language'],$this->prodArray['tracking_code'],$this->prodArray['expiration_time'],$this->prodArray['response_format'],$this->prodArray['url_safety'],$this->prodArray['signature_key']);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Function Info
        $datos=$constructor -> request($params[0],$params[1],$params[2],$params[3],$params[4]);

        //Response
        if($datos['error_number']==0){
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }else{
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
        $transaction->setSuccessful(true);
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