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
        $transaction->setMode($mode === 'P');

        //Convertimos el amount de centimos a dos decimales
        $amount=$params[1]/100;

        //Constructor
        $datos=$this->get('paynetref.service')->getPaynetGetBarcode()->request($params[0],$amount,$params[2]);

        if(isset($datos['barcode'])){
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

    public function generateTest(Request $request){
        $request->request->set('mode','T');
        return $this->generate($request);
    }


    //TODO: esto esta mal
    public function status(Request $request){

        static $paramNames = array(
            'client_reference'
        );

        //Get the parameters sent by POST
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

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Constructor
        $datos=$this->get('paynetref.service')->getPaynetGetStatus()->status($params[0]);

        //Response
        if($datos['error_code']==0){
            $rCode=201;
            $resp = new ApiResponseBuilder(
                201,
                "Request info successfully",
                $datos
            );
        }else{
            $rCode=400;
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function statusTest(Request $request){
        $request->request->set('mode','T');
        return $this->status($request);
    }

} 