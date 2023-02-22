<?php

namespace App\Controller\Management\System;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\Document\Transaction;
use App\Entity\CashInDeposit;
use App\Entity\InternalBalance;
use App\Financial\Currency;

/**
 * Class ActivityController
 * @package App\Controller\Management\System
 */
class ActivityController extends RestApiController
{

    /**
     * @Rest\View
     */
    public function last50Transactions() {

        $dm = $this->get('doctrine_mongodb')->getManager();
        $last50Trans = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
            ->limit(50)
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();

        $resArray = [];

        $em = $this->getDoctrine()->getManager();
        $groupRepo = $em->getRepository('FinancialApiBundle:Group');
        foreach($last50Trans->toArray() as $res){
            if($res->getGroup()){
                $group = $groupRepo->find($res->getGroup());
                if($group){
                    $res->setGroupData($group->getName());
                }
            }

            $resArray [] = $res;

        }

        return $this->rest(200, "ok", "Last 50 transactions got successfully", $resArray);
    }
}
