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

class ServicesSabadellTPVController extends FosRestController
{

    /**
     * Returns needed parameters to obtain Sabadell TPV.
     *
     * @ApiDoc(
     *   section="TPV Sabadell",
     *   description="This method allows client to get a TPV for finish the payment.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *       400="Returned when the request was bad",
     *   },
     *   parameters={
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction Amount in cents Eg: 100.00 = 10000."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This id must be unique."
     *      },
     *      {
     *          "name"="description",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction description."
     *      },
     *      {
     *          "name"="url_notification",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url to notify the transaction."
     *      },
     *      {
     *          "name"="url_ok",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url to redirect client if the transaction was correct."
     *      },
     *      {
     *          "name"="url_ko",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Url to redirect client if something went wrong."
     *      }
     *   }
     * )
     *
     */

    public function generate(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'amount',
            'transaction_id',
            'description',
            'url_notification',
            'url_ok',
            'url_ko'
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
            $params[1]='000'.$userid.$params[1];
        }elseif($userid<100){
            $params[1]='00'.$userid.$params[1];
        }elseif($userid<1000){
            $params[1]='0'.$userid.$params[1];
        }else{
            $params[1]=$userid.$params[1];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('net.telepay.services.sabadell')->getCname());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setDataIn($paramsMongo);

        $dms = $this->get('doctrine_mongodb')->getManager();
        $dms->persist($transaction);
        $id=$transaction->getId();

        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        $amount=$params[0];

        //Check if it's a Test or Production transaction
        $url_final='/notifications/v1/sabadell/'.$id;
        //Constructor in Test mode
        $datos=$this->get('net.telepay.provider.sabadell')->request($amount,$params[1],$params[2],$url_base,$params[4],$params[5],$url_final);

        //Guardamos la respuesta
        $transaction->setDataOut($datos);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());

        $dm->persist($transaction);
        $dm->flush();

        $datos['id_telepay']=$transaction->getId();

        $rCode=201;
        $res="Reference created successfully";
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
}