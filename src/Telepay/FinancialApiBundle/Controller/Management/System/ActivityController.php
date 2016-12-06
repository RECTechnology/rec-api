<?php

namespace Telepay\FinancialApiBundle\Controller\Management\System;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class ActivityController
 * @package Telepay\FinancialApiBundle\Controller\Management\System
 */
class ActivityController extends RestApiController
{

    /**
     * @Rest\View()
     */
    public function last50Transactions() {

        $dm = $this->get('doctrine_mongodb')->getManager();
        $last50Trans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->limit(50)
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();

        $resArray = [];

        $em = $this->getDoctrine()->getManager();
        $groupRepo = $em->getRepository('TelepayFinancialApiBundle:Group');
        foreach($last50Trans->toArray() as $res){
            if($res->getGroup()){
                $group = $groupRepo->find($res->getGroup());
                if($group){
                    $res->setGroupData($group->getName());
                }
            }


            $resArray [] = $res;

        }

        return $this->restV2(200, "ok", "Last 10 transactions got successfully", $resArray);
    }

    /**
     * @Rest\View
     */

    public function totalWallets(){
        $dm = $this->getDoctrine()->getManager();
        $groupRepo = $dm->getRepository('TelepayFinancialApiBundle:Group');
        $groups = $groupRepo->findBy(
            array('own'=>true)
        );
        $chipchap_groups = array();
        foreach($groups as $group){
            $chipchap_groups[] = $group->getId();
        }

        $qb = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserWallet')->createQueryBuilder('w');
        $qb->Select('SUM(w.available) as available, SUM(w.balance) as balance, w.currency')
            ->where('w.group NOT IN (:groups)')
            ->setParameter('groups', $chipchap_groups)
            ->groupBy('w.currency');

        $query = $qb->getQuery()->getResult();

        //montamos el wallet
        $multidivisa = [];
        $multidivisa['id'] = 'multidivisa';
        $multidivisa['currency'] = 'EUR';
        $multidivisa['available'] = 0;
        $multidivisa['balance'] = 0;
        $multidivisa['scale'] = 2;

        $filtered = [];

        foreach($query as $wallet){
            $wallet['id'] = $wallet['currency'];
            $wallet['available'] = round($wallet['available'],0);
            $wallet['balance'] = round($wallet['balance'],0);
            $wallet['scale'] = Currency::$SCALE[$wallet['currency']];
            $filtered[] = $wallet;
            if($wallet['currency'] != 'EUR'){
                $multidivisa['available'] = $multidivisa['available'] + $this->exchange($wallet['available'], $wallet['currency'], 'EUR');
                $multidivisa['balance'] = $multidivisa['balance'] + $this->exchange($wallet['balance'], $wallet['currency'], 'EUR');
            }else{
                $multidivisa['available'] = $multidivisa['available'] + $wallet['available'];
                $multidivisa['balance'] = $multidivisa['balance'] + $wallet['balance'];
            }

        }

        $filtered[] = $multidivisa;

        return $this->restV2(200, "ok", "Total wallet info got successfully", $filtered);

    }

    /**
     * makes an exchange between currencies in the wallet
     */
    private function exchange($amount, $src, $dst){

        $dm = $this->getDoctrine()->getManager();
        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$src,'dst'=>$dst),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found');

        $price = $exchange->getPrice();

        $response = $amount * $price;

//        die(print_r($amount.' - '.$src.' - '.$dst.' - '.$price.' - '.$response,true));

        return round($response,0);

    }
}
