<?php

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;



class ServicesPaynetReferenceController extends FosRestController
{
    /**
     * Returns a code and after we have to put this code into a url to obtain the barcode.
     *
     * @ApiDoc(
     *   section="Paynet Reference",
     *   description="This method allows client to get a barcode with the reference for the payment",
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
     */

    public function generate(Request $request){

        static $paramNames = array(
            'client_reference',
            'amount',
            'description'
        );

        //Get the parameters sent by POST
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
        $transaction->setService($this->get('telepay.services')->findByName('PaynetReference')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode);

        //Constructor
        $datos=$this->get('paynetref.service')->getPaynetGetBarcode()->request($params[0],$params[1],$params[2]);

        if(isset($datos['barcode'])){
            $transaction->setSuccessful(true);
            $rCode=400;
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }else{
            $transaction->setSuccessful(false);
            $rCode=201;
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
        $transaction->setCompleted(false);

        $dm->persist($transaction);
        $dm->flush();

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);
    }

    public function generateTest(Request $request){
        $request->request->set('mode','T');
        return $this->generate($request);
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
     */

    public function status(){

        static $paramNames = array(
            'client_reference'
        );

        //Get the parameters sent by POST
        $request=$this->get('request_stack')->getCurrentRequest();
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

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PaynetReference')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode(true);

        //Constructor
        $datos=$this->get('paynetref.service')->getPaynetGetStatus()->status($params[0]);

        //Response
        if($datos['error_code']==0){
            $transaction->setSuccessful(false);
            $rCode=400;
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }else{
            $transaction->setSuccessful(true);
            $rCode=201;
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

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function statusTest(Request $request){
        $request->request->set('mode','T');
        return $this->status($request);
    }

} 