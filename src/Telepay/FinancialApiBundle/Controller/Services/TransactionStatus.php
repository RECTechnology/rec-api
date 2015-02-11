<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 9:11 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;


class TransactionStatus extends FOSRestController{

    public function status(Request $request,$id){

        $user=$this->get('security.context')->getToken()->getUser()->getId();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $tid=$id;
        $query = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('id')->equals($tid)
            ->field('user')->equals($user)
            ->getQuery()->execute();

        $transArray = [];
        foreach($query->toArray() as $transaction){
            $transArray []= $transaction;
        }
        $result=$transArray[0];



        $resp = new ApiResponseBuilder(
            $rCode=201,
            "Transaction info got succesfull",
            $result
        );

        $view = $this->view($resp, $rCode);

        return $this->handleView($view);


    }

    public function statusTest(Request $request,$id){
        $request->request->set('mode','T');
        return $this->status($request,$id);
    }

}