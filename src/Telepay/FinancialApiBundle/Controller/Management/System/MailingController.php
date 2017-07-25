<?php

namespace Telepay\FinancialApiBundle\Controller\Management\System;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\Mail;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class MailingController
 * @package Telepay\FinancialApiBundle\Controller\System
 */
class MailingController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Mail";
    }

    function getNewEntity()
    {
        return new Mail();
    }

    /**
     * @Rest\View
     * description: returns all mails
     * permissions: ROLE_SUPER_ADMIN ( all)
     */
    public function indexAction(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 100;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        //only the superadmin can access here
        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You have not the necessary permissions');

        $em = $this->getDoctrine()->getManager();
        $mails = $em->getRepository($this->getRepositoryName())->findAll();

        $total = count($mails);
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($mails)+$offset,
                'elements' => $mails
            )
        );

    }

    /**
     * @Rest\View
     * description: create a mail
     */
    public function createAction(Request $request){

        //only the superadmin can access here
        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You have not the necessary permissions');

        return parent::createAction($request);
    }

    /**
     * @Rest\View
     */
    public function showAction($id){

        $em = $this->getDoctrine()->getManager();

        $mail = $em->getRepository($this->getRepositoryName())->find($id);
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => 1,
                'start' => 0,
                'end' => 1,
                'elements' => $mail
            )
        );
    }

    /**
     * @Rest\View
     * Permissions: ROLE_SUPER_ADMIN (all) , ROLE_RESELLER(sub-companies)
     */
    public function updateAction(Request $request, $id){

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);

    }

}
