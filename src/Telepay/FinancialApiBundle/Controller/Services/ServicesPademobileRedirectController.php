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

class ServicesPademobileRedirectController extends FosRestController
{



    public function request(Request $request){

        static $paramNames = array(
            'country',
            'url',
            'description',
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

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Pademobile')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        $dms = $this->get('doctrine_mongodb')->getManager();
        $dms->persist($transaction);
        $id=$transaction->getId();

        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_notification=$url_base.'/notifications/v1/pademobile?tid='.$id;

        //Constructor
        $datos = $this->get('pademobile.service')
            ->getPademobile($mode)
            ->request($params[0],$url_notification,$params[2],$params[3]);

        $transaction->setSuccessful(true);

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setCompleted(false);

        $dm->persist($transaction);
        $dm->flush();

        $rCode=302;

        $resp = new ApiResponseBuilder(
            $rCode,
            "New Location generated successfully",
            $datos
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function requestTest(Request $request){
        $request->request->set('mode','T');
        return $this->request($request);
    }



    public function requestV2(Request $request){

        static $paramNames = array(
            'country',
            'url',
            'description',
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

        //Comprobamos modo Test
        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Pademobile')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        $dms = $this->get('doctrine_mongodb')->getManager();
        $dms->persist($transaction);
        $id=$transaction->getId();

        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $url_notification=$url_base.'/notifications/v1/pademobile?tid='.$id;

        //Convertimos el amount de centimos a decimales para enviarlos a pademobile
        $amount=$params[3]/100;

        //Constructor
        $datos = $this->get('pademobile.service')
            ->getPademobile($mode)
            ->request($params[0],$url_notification,$params[2],$amount);

        $transaction->setSuccessful(true);

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setCompleted(false);

        $dm->persist($transaction);
        $dm->flush();

        $rCode=302;
        $datos['id_telepay']=$transaction->getId();
        $resp = new ApiResponseBuilder(
            $rCode,
            "New Location generated successfully",
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