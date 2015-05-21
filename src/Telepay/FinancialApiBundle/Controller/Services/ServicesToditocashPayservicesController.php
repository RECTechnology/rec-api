<?php

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;


class ServicesToditocashPayservicesController extends FosRestController
{

    public function request(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'transaction_id',
            'date',
            'hour',
            'card_number',
            'nip',
            'amount',
            'concept',
            'currency'
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

        $paramsMongo['card_number']=substr_replace($paramsMongo['card_number'], '************', 0, -4);

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

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setService($this->get('telepay.services')->findByName('ToditoCash')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        $amount=$params[5]/100;

        if($mode=='T'){
            //Request method
            $datos=$this->get('todito.service')->getToditoCash()-> request($params[0],$params[1],$params[2],$params[3],$params[4],$amount,$params[6],$params[7],'0');
        }elseif($mode=='P'){
            //Request method
            $datos=$this->get('todito.service')->getToditoCash()-> request($params[0],$params[1],$params[2],$params[3],$params[4],$amount,$params[6],$params[7],'1');
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Bad Request,check mode');
        }

        //Quitamos el id de usuario para devolverle el transaction_id al cliente
        $datos['transaction_id']=substr($datos['transaction_id'],1);

        if($datos['status']!='000'||isset($datos['error'])){
            $transaction->setSuccessful(false);
            $transaction->setCompleted(false);
            $rCode=400;
            $res="Bad request";

        }else{
            $transaction->setSuccessful(true);
            $transaction->setCompleted(true);
            $rCode=201;
            $res="Reference created successfully";

        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();

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

    //TODO: esto esta mal
    public function reverso(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'tc_number_transaction',
            'transaction_id',
            'date',
            'hour',
            'card_number',
            'amount'
        );

        //Get the parameters and put them in a $params array
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

        $paramsMongo['card_number']=substr_replace($paramsMongo['card_number'], '************', 0, -4);

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
        $transaction->setService($this->get('telepay.services')->findByName('ToditoCash')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        if($mode=='T'){
            //Reverso test method
            $datos=$this->get('todito.service')->getToditoCash()-> reverso($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],'0');
        }elseif($mode=='P'){
            //Reverso production method
            $datos=$this->get('todito.service')->getToditoCash()-> reverso($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],'1');
        }

        //Quitamos el id de usuario para devolverle el transaction_id al cliente
        $datos['transaction_id']=substr($datos['transaction_id'],1);

        if($datos['status']=='135'){
            $transaction->setSuccessful(true);
            $rCode=201;
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }else{
            $transaction->setSuccessful(false);
            $rCode=400;
            $resp = new ApiResponseBuilder(
                400,
                "Bad Request",
                $datos['status_message']
            );
        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setCompleted(true);
        $dm->persist($transaction);
        $dm->flush();

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);
    }

    public function reversoTest(Request $request){
        $request->request->set('mode','T');
        return $this->reverso($request);

    }

}