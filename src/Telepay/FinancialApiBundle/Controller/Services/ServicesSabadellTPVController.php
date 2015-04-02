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

        //Concatenamos la referencia añadiendole el idusuario (0000) i le ponemos 2 ceros detras
        //que son como un contador para que la misma tpv se pueda generar varias veces
        if($userid < 10){
            $params[1]='000'.$userid.$params[1].'00';
        }elseif($userid<100){
            $params[1]='00'.$userid.$params[1].'00';
        }elseif($userid<1000){
            $params[1]='0'.$userid.$params[1].'00';
        }else{
            $params[1]=$userid.$params[1].'00';
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('net.telepay.services.sabadell.v1')->getCname());
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


    public function regenerate(Request $request,$id){

        $user=$this->get('security.context')->getToken()->getUser()->getId();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $tid=$id;
        $query = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('id')->equals($tid)
            ->field('user')->equals($user)
            ->getQuery()->execute();

        if(!$query){
            throw new HttpException(400,'User not found');
        }

        $transArray = [];
        foreach($query->toArray() as $transaction){
            $transArray []= $transaction;
            //die(print_r($transaction,true));
        }

        //RECUPERAMOS TODOS LOS PARAMETROS Y VOLVEMOS A MONTAR LA TPV PARA PODER CAMBIAR EL TRANSACTION ID Y VOLVER A CALCULAR AL FIRMA Y TO DO

        $tpv_data=$transArray[0]->getSentData();

        $tpv_data=json_decode(($tpv_data));

        $tpv_data=get_object_vars($tpv_data);

        $mode='P';

        if($mode==true){
            $mode='P';
        }else{
            $mode='T';
        }

        $amount=$tpv_data['amount'];
        $tpv_data['transaction_id']=$tpv_data['transaction_id']+1;
        $transaction_id=$tpv_data['transaction_id'];
        $description=$tpv_data['description'];
        $url_ok=$tpv_data['url_ok'];
        $url_ko=$tpv_data['url_ko'];

        $url_base=$request->getSchemeAndHttpHost().$request->getBaseUrl();

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            $url_notification=$url_base.'/test/notifications/v1/sabadell/'.$id;
            //Constructor in Test mode
            $datos=$this->get('sabadell.service')->getSabadellTest($amount,$transaction_id,$description,$url_notification,$url_ok,$url_ko)-> request();
        }elseif($mode=='P'){
            $url_notification=$url_base.'/notifications/v1/sabadell/'.$id;
            //Constructor in Production mode
            $datos=$this->get('sabadell.service')->getSabadell($amount,$transaction_id,$description,$url_notification,$url_ok,$url_ko)->request();
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require->Test with T or P');
        }

        $trans=$transArray[0];
        $trans->setSentData(json_encode($tpv_data));
        $trans->setReceivedData(json_encode($datos));


        $dms = $this->get('doctrine_mongodb')->getManager();
        $dms->persist($trans);
        $dms->flush();


        $result=$trans->getReceivedData();

        $result=json_decode($result);

        $result=get_object_vars($result);

        $resp = new ApiResponseBuilder(
            $rCode=201,
            "Transaction info got succesfull",
            $result
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);

    }

    public function regenerateTest(Request $request,$id){
        $request->request->set('mode','T');
        return $this->regenerate($request,$id);
    }
}