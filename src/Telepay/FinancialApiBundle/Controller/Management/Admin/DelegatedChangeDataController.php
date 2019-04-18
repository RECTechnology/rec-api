<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use DateTime;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\DelegatedChangeData;

/**
 * Class DelegatedChangeDataController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class DelegatedChangeDataController extends BaseApiController{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\View
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request); // TODO: Change the autogenerated stub
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\View
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $dc = $em->getRepository("TelepayFinancialApiBundle:DelegatedChange")
            ->find($request->request->get('delegated_change'));

        $account = $em->getRepository("TelepayFinancialApiBundle:Group")
            ->find($request->request->get('account'));
        if($account->hasRole('ROLE_COMPANY')){
            throw new HttpException(403,"Expect a user not a commerce!");
        }

        $exchanger = $em->getRepository("TelepayFinancialApiBundle:Group")
            ->find($request->request->get('exchanger'));
        if( !$exchanger->hasRole('ROLE_COMPANY')){
            throw new HttpException(403,"Expect a commerce not a user!");
        }

        if($request->request->has('expiry_date')){
           $expiry_date = DateTime::createFromFormat('Y-m-d', $request->request->get('expiry_date'));
           $expiry_date->setTime(00, 00, 00);
           if($expiry_date < new DateTime()){
               throw new HttpException(403,"The card is expired!");
           }
            $request->request->set('expiry_date', $expiry_date);
        }

        $request->request->add(array('delegated_change'=> $dc, 'account'=> $account, 'exchanger'=> $exchanger));
        return parent::createAction($request); // TODO: Change the autogenerated stub
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\View
     */
        public function showAction($id)
    {
        return parent::showAction($id); // TODO: Change the autogenerated stub
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\View
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if($request->request->has('delegated_change')){
            $dc = $em->getRepository("TelepayFinancialApiBundle:DelegatedChange")
                ->find($request->request->get('delegated_change'));
            $request->request->set('delegated_change', $dc);
        }
        if($request->request->has('account')){
            $account = $em->getRepository("TelepayFinancialApiBundle:Group")
                ->find($request->request->get('account'));
            if($account->hasRole('ROLE_COMPANY')){
                throw new HttpException(403,"Expect a user not a commerce!");
            }
            $request->request->set('account', $account);
        }
        if($request->request->has('account')){
            $exchanger = $em->getRepository("TelepayFinancialApiBundle:Group")
                ->find($request->request->get('exchanger'));
            if( !$exchanger->hasRole('ROLE_COMPANY')){
                throw new HttpException(403,"Expect a commerce not a user!");
            }
            $request->request->set('exchanger', $exchanger);
        }

        if($request->request->has('expiry_date')){
            $expiry_date = DateTime::createFromFormat('Y-m-d', $request->request->get('expiry_date'));
            $expiry_date->setTime(00, 00, 00);
            if($expiry_date < new DateTime()){
                throw new HttpException(403,"The card is expired!");
            }
            $request->request->set('expiry_date', $expiry_date);
        }



        return parent::updateAction($request, $id); // TODO: Change the autogenerated stub
    }


    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\View
     */
    public function deleteAction($id)
    {
        return parent::deleteAction($id); // TODO: Change the autogenerated stub
    }


    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:DelegatedChangeData";
    }

    function getNewEntity()
    {
        return new DelegatedChangeData();
    }
}