<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Admin;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\Entity\Balance;
use App\FinancialApiBundle\Financial\Currency;

class BalancesController extends RestApiController{

    public function getRepositoryName(){
        return 'FinancialApiBundle:Balance';
    }

    public function getNewEntity(){
        return new Balance();
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
//        return parent::showAction($id);
        throw new HttpException(403, 'Method not implemented');
    }

    /**
     * @Rest\View
     */
    public function indexAction(Request $request, $company_id){
//        die(print_r('caca',true));
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($company_id);
        $balances = $em->getRepository('FinancialApiBundle:Balance')->findBy(array(
            'group'   =>  $company
        ));

        foreach ($balances as $balance){
            $balance->setScale(Currency::$SCALE[$balance->getCurrency()]);
        }

        return $this->restV2(200,"ok", "Request successful", $balances);
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){
//        return parent::createAction($request);
        throw new HttpException(403, 'Method not implemented');
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $company_id, $id){
//        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
//        return parent::deleteAction($id);
    }

    /**
     * @Rest\View
     */
    public function resetBalances($company_id){
        //search all balances for this company
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($company_id);
        $balances = $em->getRepository($this->getRepositoryName())->findBy(array(
            'group' =>  $company_id
        ));

        foreach ($balances as $balance){
            $em->remove($balance);
            $em->flush();
        }

        $wallets = $company->getWallets();
        foreach ($wallets as $wallet){
            $balance = new Balance();
            $balance->setCurrency($wallet->getCurrency());
            $balance->setConcept('Start Balance');
            $balance->setAmount($wallet->getAvailable());
            $balance->setBalance($wallet->getAvailable());
            $balance->setDate(new \DateTime());
            $balance->setGroup($company);
            $balance->setTransactionId(0);

            $em->persist($balance);
            $em->flush();
        }

        return $this->restV2(204, 'success', 'Successfully balances restart');

    }



}