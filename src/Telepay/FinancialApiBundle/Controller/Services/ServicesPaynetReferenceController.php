<?php

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use PaynetGetBarcode;
use PaynetGetStatus;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;



class ServicesPaynetReferenceController extends FosRestController
{
    /**
     * This method allows client to get a barcode with the reference for the payment.
     *
     * @ApiDoc(
     *   section="Paynet Reference",
     *   description="Returns a pdf file with the barcode and the instructions",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="client_reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Pay/Product reference/identifier. (max 12 chars).Ex: '000000000000'"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Allowed mount. Ex: '00001000'"
     *      },
     *      {
     *          "name"="description",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Product description. Ex: 'television'"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function generate(){

        static $paramNames = array(
            'client_reference',
            'amount',
            'description'
        );

        //Get the parameters sent by POST
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PaynetReference')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($params));
        $transaction->setMode(true);


        //Include the class
        include("../vendor/paynet-barcode/PaynetGetBarcode.php");

        //Constructor
        $constructor=new PaynetGetBarcode($params[0],$params[1],$params[2]);

        //Request method
        $datos=$constructor -> request();

        if(isset($datos['barcode'])){
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

    /**
     * This method allows client to know the status reference.
     *
     * @ApiDoc(
     *   section="Paynet Reference",
     *   description="Returns an array with the status",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="client_reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Pay/Product reference/identifier. (max 12 chars).Ex: '000000000000'"
     *      }
     *   },
     *   output="Telepay\FinancialApiBundle\Controller\Response"
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function status(){

        static $paramNames = array(
            'client_reference'
        );

        //Get the parameters sent by POST
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PaynetReference')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($params));
        $transaction->setMode(true);

        //Include the class
        include("../vendor/paynet-barcode/PaynetGetStatus.php");

        //Constructor
        $constructor=new PaynetGetStatus($params[0]);

        //Status method
        $datos=$constructor -> status();

        //Response
        if($datos['error_code']==0){
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

} 