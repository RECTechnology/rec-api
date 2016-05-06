<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 9:11 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;

class NotificationsController extends RestApiController{

    public function notificate(Request $request, $version_number, $service_cname, $id = null){

        $logger = $this->_logger();

        if($service_cname == 'safetypay'){
            $notification = $this->_safetypayNotification($request);
        }else{
            $notification = false;
        }

        if($notification == 'notified'){
            return $this->rest(204, 'No content');
        }

    }

    public function _safetypayNotification(Request $request){

        $logger = $this->_logger();

        $logger->info('notifications -> _safetypay notification');
        $cashInMethod = $this->container->get('net.telepay.in.safetypay.v1');

        //Locate transaction
        $tid = $request->request->get('MerchantSalesID');

        $logger->info('notifications -> tid => '.$tid);

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transaction = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('pay_in_info.reference')->equals($tid)
            ->getQuery()
            ->getSingleResult();

        if(!$transaction) throw new HttpException(404, 'Transaction not found');
        $logger->info('notifications -> transaction found');

        $paymentInfo = $transaction->getPayInInfo();

        $allParams = $request->request->all();
        $params = array();
        foreach($allParams as $key => $value){
            $logger->info('notifications -> '.$key.' => '.$value);
            $params[$key] = $value;
        }

        $signature_key = '4774d66c4d24b7091ce3a261b0e5a990';

        $dataToSign = $params['RequestDateTime'].$params['MerchantSalesID'].$params['ReferenceNo'].$params['CreationDateTime'].$params['Amount'].$params['CurrencyID'].$params['PaymentReferenceNo'].$params['Status'].$signature_key;
        $signature = hash('sha256', $dataToSign,false);

        $logger->info('notifications -> calculated signature => '.strtoupper($signature));
        $logger->info('notifications -> received   signature => '.$params['Signature']);
        if(strtoupper($signature) == $params['Signature']){
            if($params['Status'] == 102){
                $logger->info('notifications -> Status => '.$params['Status']);
                $response = array(
                    'status'    =>  1,
                    'params'    =>  $params
                );
            }else{
                $response = array(
                    'status'    =>  0,
                    'params'    =>  $signature
                );
                $logger->info('notifications -> Status => bad code');
            }

        }else{
            $response = array(
                'status'    =>  0,
                'params'    =>  $signature
            );
            $logger->info('notifications -> Status => bad signature');
        }

//        $paymentInfo = $cashInMethod->notification($request, $paymentInfo);
//        $logger->info('notifications -> status => '.$paymentInfo['status']);
//        if($paymentInfo['status'] == 'received'){
//            $transaction->setStatus('received');
//            $transaction->setPayInfo($paymentInfo);
//            $dm->persist($transaction);
//            $dm->flush();
//
//        }else{
//            $logger->info('notifications -> debug => '.$paymentInfo['debug']);
//        }

        return 'notified';

    }

    public function paynetreferenceNotification(Request $request){
        //aun no sabemos como sera

    }

    public function pademobileNotification(Request $request){
        //solo parametros Get tid,codtran,status y message

        static $paramNames=array(
            'tid',
            'codtran',
            'status',
            'message'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->query ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->query->get($paramName, 'null');
        }

        if($params[2]==true){
            $tid=$params[0];
            $dm = $this->get('doctrine_mongodb')->getManager();
            //die(print_r($userId,true));
            $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                ->find($tid);

            $transactions->setCompleted(true);

            $dm->persist($transactions);
            $dm->flush();

            $query = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('id')->equals($tid)
                ->getQuery()->execute();
            //die(print_r($transactions, true));
            $transArray = [];
            foreach($query->toArray() as $transaction){
                $transArray []= $transaction;
            }

            $result=$transArray[0];

            $result=$result->getSentData();
            $result=json_decode($result);
            $result=get_object_vars($result);

            return $this->redirect($result['url']);

        }else{
            $tid=$params[0];
            $dm = $this->get('doctrine_mongodb')->getManager();
            //die(print_r($userId,true));
            $query = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('id')->equals($tid)
                ->getQuery()->execute();
            //die(print_r($transactions, true));
            $transArray = [];
            foreach($query->toArray() as $transaction){
                $transArray []= $transaction;
            }

            $result=$transArray[0];

            $result=$result->getSentData();
            $result=json_decode($result);
            $result=get_object_vars($result);

            return $this->redirect($result['url']);

            //header('Location: '.$result['url_fail']);

        }

    }

    public function ukashredirectNotification(Request $request){
        //parametros get y post porque necesitamos el utid para confirmar
        static $getNames=array(
            'tid'
        );

        static $postNames=array(
            'utid'
        );

        $paramsGet = array();
        $paramsPost=array();
        foreach($getNames as $paramName){
            if(!$request->query ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $paramsGet[]=$request->query->get($paramName, 'null');
        }

        foreach($postNames as $pramName){
            if(!$request->request ->has($pramName)){
                throw new HttpException(400,"Missing parameter '$pramName'");
            }
            $paramsPost[]=$request->request->get($pramName, 'null');
        }

        //die(print_r($paramsPost,true));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $tid=$paramsGet[0];
        $query = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('id')->equals($tid)
            ->getQuery()->execute();

        $transArray = [];
        foreach($query->toArray() as $transaction){
            $transArray []= $transaction;
        }

        //die(print_r($transArray,true));

        $consulta=$transArray[0];
        $utid=$consulta->getReceivedData();
        $utid=json_decode($utid);
        $utid=get_object_vars($utid);

        $utid=$utid['utid'];

        if($paramsPost[0]=$utid){
            $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                ->find($tid);

            $transactions->setCompleted(true);

            $dm->persist($transactions);
            $dm->flush();

            $url_notification=$consulta->getSentData();
            $url_notification=json_decode($url_notification);
            $url_notification=get_object_vars($url_notification);
            $url_notification=$url_notification['url_notification'];

            //faltaria notificarselo al cliente
            $data=array(
                'utid'  => $utid
            );

            // create curl resource
            $ch = curl_init();
            // set url
            curl_setopt($ch, CURLOPT_URL, $url_notification);
            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
            // $output contains the output string
            $output = curl_exec($ch);
            // close curl resource to free up system resources
            curl_close($ch);
            //die(print_r($output,true));
            $view = $this->view('OK', '200');

            return $this->handleView($view);

        }else{

            $view = $this->view('KO', '503');

            return $this->handleView($view);

        }



    }

    public function multivaNotification(Request $request){

        static $getNames=array(
            'tid'
        );

        static $postNames=array(
            'EM_Response',
            'EM_Total',
            'EM_OrderID',
            'EM_Merchant',
            'EM_Store',
            'EM_Term',
            'EM_RefNum',
            'EM_Auth',
            'EM_Digest'
        );

        $paramsPost=array();
        $paramsGet = array();

        foreach($postNames as $pramName){
            if(!$request->request ->has($pramName)){
                throw new HttpException(400,"Missing parameter '$pramName'");
            }
            $paramsPost[]=$request->request->get($pramName, 'null');
        }

        foreach($getNames as $paramName){
            if(!$request->query ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $paramsGet[]=$request->query->get($paramName, 'null');
        }

        //Comprobamos el digest para saber que la transaccion viene de PROSA y no ha sido alterada
        $newdigest  = sha1($paramsPost[1].$paramsPost[2].$paramsPost[3].$paramsPost[4].$paramsPost[5].$paramsPost[6]."-".$paramsPost[7]);

        if($newdigest==$paramsPost[8]){
            $dm = $this->get('doctrine_mongodb')->getManager();
            $tid=$paramsGet[0];
            $query = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('id')->equals($tid)
                ->getQuery()->execute();

            $transArray = [];
            foreach($query->toArray() as $transaction){
                $transArray []= $transaction;
            }
            $result=$transArray[0];

            if($paramsPost[0]=='approved'){
                $status=1;
                $result->setCompleted(true);
                $dm->persist($result);
                $dm->flush();
            }else{
                $status=0;
            }

            $redirect=$result->getSentData();
            $redirect=json_decode($redirect);
            $redirect=get_object_vars($redirect);

            $cadena='?';
            $busqueda = strpos($redirect['url_notification'], $cadena);
            if($busqueda===false){
                //die(print_r($redirect['url_notification'],true));
                $redirect['url_notification']=$redirect['url_notification'].'?status='.$status;
            }else{
                //die(print_r($redirect['url_notification'],true));
                $redirect['url_notification']=$redirect['url_notification'].'&status='.$status;
            }
            return $this->redirect($redirect['url_notification']);

        }else{
            //que hacemos si no es verdadera.?? supongo que redirecciona
            $view = $this->view('KO', '503');

            return $this->handleView($view);
        }

    }

    public function sabadellNotification(Request $request,$id){

        static $paramNames = array(
            'Ds_Date',
            'Ds_Hour',
            'Ds_Amount',
            'Ds_Currency',
            'Ds_Order',
            'Ds_MerchantCode',
            'Ds_Terminal',
            'Ds_Signature',
            'Ds_Response',
            'Ds_TransactionType',
            'Ds_SecurePayment',
            'Ds_MerchantData',
            'Ds_Card_Country',
            'Ds_AuthorisationCode',
            'Ds_ConsumerLenguage',
            'Ds_Card_Type'
        );

        $params=array();
        foreach ($paramNames as $paramName){
            $params[]=$request->get($paramName, 'null');
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        if($mode=='T'){
            // Compute hash to sign form data
            // $signature=sha1_hex($amount,$order,$code,$currency,$response,$clave);
            $message = $params[2].$params[4].$params[5].$params[3].$params[8].$this->container->getParameter('sabadell_secret_test');
        }else{
            // Compute hash to sign form data
            // $signature=sha1_hex($amount,$order,$code,$currency,$response,$clave);
            $message = $params[2].$params[4].$params[5].$params[3].$params[8].$this->container->getParameter('sabadell_secret');
        }

        $signature = strtoupper(sha1($message));

        if($signature==$params[7]){

            $dm = $this->get('doctrine_mongodb')->getManager();
            $query = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('id')->equals($id)
                ->getQuery()->execute();

            $transArray = [];
            foreach($query->toArray() as $transaction){
                $transArray []= $transaction;
            }
            $result=$transArray[0];

            if (!$result) {
                throw new HttpException(400,'No transaction found');
            }

            $transaction_id=$result->getId();

            $redirect=$result->getSentData();
            $redirect=json_decode($redirect);
            $redirect=get_object_vars($redirect);

            if($params[8]<=99){

                $status=1;
                $result->setCompleted(true);
                $dm->persist($result);
                $dm->flush();

            }else{

                $status=0;

            }

            $fields=array(
                'telepay_id'    =>  $transaction_id,
                'status'        =>  $status
            );

            // create curl resource
            $ch = curl_init();
            // set url
            curl_setopt($ch, CURLOPT_URL, $redirect['url_notification']);
            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
            // $output contains the output string
            $output = curl_exec($ch);
            // close curl resource to free up system resources
            curl_close($ch);



            $view = $this->view('OK', '200');

            return $this->handleView($view);

        }

        $view = $this->view('Error', '402');

        return $this->handleView($view);

    }

    public function sabadellNotificationTest(Request $request,$id){
        $request->request->set('mode','T');
        return $this->sabadellNotification($request,$id);
    }

    private function _logger(){
        $logger = $this->container->get('logger');

        return $logger;
    }
}