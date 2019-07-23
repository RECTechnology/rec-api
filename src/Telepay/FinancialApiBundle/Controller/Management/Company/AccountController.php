<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Doctrine\ORM\EntityManagerInterface;
use Telepay\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserGroup;

class AccountController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }


    /**
     * @Rest\View
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setAdmin(Request $request, $id){

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$company) throw new HttpException(404, 'Company not found');

        $userGroup = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $company->getId()
        ));

        if(!$userGroup) throw new HttpException(404, 'Change not allowed');

        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException('You don\'t have the necessary permissions');

        $company->setKycManager($user);
        $em->flush();

        return $this->rest(204, 'Manager changed successfully');

    }


    /**
     * @Rest\View
     * Permissions: ROLE_ADMIN (all)
     * @param Request $request
     * @param $account_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request, $account_id){

        $admin = $this->get('security.token_storage')->getToken()->getUser();
        $adminGroup = $this->getRepository($this->getRepositoryName())->find($account_id);

        $adminRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $admin->getId(),
            'group' =>  $adminGroup->getId()
        ));

        if(!$adminRoles){
            throw new HttpException(403, 'You are not in this account');
        }

        if(!$adminRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        if($adminGroup->getFixedLocation()){
            $request->request->remove('longitude');
            $request->request->remove('latitude');
        }

        //check some params that can't be modified from here
        $invalid_params = array(
            'access_key',
            'access_secret',
            'active',
            'rec_address',
            'key_chain',
            'tier',
            'fixed_location',
            'type',
            'subtype'
        );

        $all = $request->request->all();
        foreach ($all as $key=>$value){
            if(in_array($key,$invalid_params))
                throw new HttpException(403, 'You don\'t have the necessary permissions to change this params. Please check documentation');
        }

        if($request->request->has('country')){
            $userCountry = $request->request->get('country');
            if(strlen($userCountry) != 3)
                throw new HttpException(
                    400,
                    "Country code must be ISO_3166-1_alpha-3 compliant, (Spain: ESP, France: FRA, more info https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3)"
                );
        }

        return parent::updateAction($request, $account_id);

    }

    /**
     * @Rest\View
     * Permissions: ROLE_ADMIN (all)
     */
    public function updateLocationAction(Request $request, $account_id){
        $admin = $this->get('security.token_storage')->getToken()->getUser();
        $adminGroup = $this->getRepository($this->getRepositoryName())->find($account_id);

        $em = $this->getDoctrine()->getManager();

        $adminRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $admin->getId(),
            'group' =>  $adminGroup->getId()
        ));

        if(!$adminRoles){
            throw new HttpException(403, 'You are not in this account');
        }

        if(!$adminRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        if($request->request->has('deactivate') && $request->request->get('deactivate') == '1') {
            $adminGroup->setFixedLocation(false);
            $em->flush();
            return $this->rest(204, 'Company location deactivated successfully');
        }

        if(!$request->request->has('latitude')) {
            throw new HttpException(400, 'latitude is required');
        }
        if(!$request->request->has('longitude')) {
            throw new HttpException(400, 'longitude is required');
        }

        $lat = $request->request->get('latitude');
        $lon = $request->request->get('longitude');

        if(intval($lat) > 90 ||  intval($lat) < -90 || intval($lat) == 0){
            throw new HttpException(400, 'Bad value for latitude (allowed float [-90, 90])');
        }

        if(intval($lon) > 90 ||  intval($lon) < -90 || intval($lon) == 0){
            throw new HttpException(400, 'Bad value for longitude (allowed float [-90, 90])');
        }

        $adminGroup->setLatitude($lat);
        $adminGroup->setLongitude($lon);
        $adminGroup->setFixedLocation(true);
        $em->flush();

        return $this->rest(200, 'Company location updated successfully');
    }
}