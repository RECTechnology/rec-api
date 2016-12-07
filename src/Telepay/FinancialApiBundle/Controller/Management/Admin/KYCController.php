<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use FOS\OAuthServerBundle\Propel\RefreshTokenQuery;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Telepay\FinancialApiBundle\Entity\KYC;

class KYCController extends BaseApiController{

    public function getRepositoryName(){
        return 'TelepayFinancialApiBundle:KYC';
    }

    public function getNewEntity(){
        new KYC();
    }

    /**
     * @Rest\View
     */
    public function listPendingIssues(Request $request){

        //only superadmin can access here
        $repository = $this->getDoctrine()->getRepository($this->getRepositoryName());


        $query = $repository->createQueryBuilder('k')
            ->where('k.tier1_status = :status')
            ->orWhere('k.tier2_status = :status')
            ->setParameter('status', 'pending')
            ->getQuery();

        $list = $query->getResult();

        return $this->restV2(201, 'success', 'List of pending Kyc successfully', $list);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        //check values that can be changed from here
        $validParams = array(
            'email_validated',
            'phone_validated',
            'full_name_validated',
            'date_birth_validated',
            'country_validated',
            'address_validated',
            'proof_of_residence',
            'document_validated'
        );

        $params = $request->request->all();
        foreach($params as $param){
            if(!in_array($param, $validParams)) throw new HttpException(404, 'Invalid param '.$param);
        }

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function updateActionOld(Request $request, $id){

        $paramNames = array(
            'tier',
            'status'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName)) throw new HttpException(404, 'Param '.$paramName.' not found');
            $params[$paramName] = $request->request->get($paramName);
        }

        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$kyc) throw new HttpException(404, 'KYC not found');

        $acceptedStatus = array(
            'denied',
            'success'
        );

        if(!in_array($params['status'], $acceptedStatus)) throw new HttpException(403, 'Value for status not valid');

        if($params['tier'] == 1){
            $kyc->setTier1Status($params['status']);
            //TODO update company tier
        }elseif($params['tier'] == 2){
            if($kyc->getTier1Status() != 'success') throw new HttpException(403, 'You needs to validate tier 1 first');
            $kyc->setTier2Status($params['status']);
            //TODO update company tier
        }


        $em->persist($kyc);
        $em->flush();

        return $this->rest(204, 'Updated successfully');
    }

}