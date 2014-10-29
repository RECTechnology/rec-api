<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 9:11 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Notifications;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;


class NotificationsController extends FOSRestController{

    public function notify(Request $request){

        static $paramNames=array(
            'tid',
            'error'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->query ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->query->get($paramName, 'null');
        }
        //die(print_r($params,true));
        if($params[1]=='0'){
            $tid=$params[0];
            $dm = $this->get('doctrine_mongodb')->getManager();
            //die(print_r($userId,true));
            $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                ->find($tid);

            $transactions->setCompleted(true);

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

            return $this->redirect($result['url_success']);


            //header('Location: '.$result['url_success']);

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

            return $this->redirect($result['url_fail']);

            //header('Location: '.$result['url_fail']);

        }




        /*return $this->handleRestView(
            200,
            "Request successful",
            $result
        );*/

    }
}