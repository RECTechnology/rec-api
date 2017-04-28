<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class GroupsController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class GroupsController extends BaseApiController
{
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
     * description: returns all groups
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

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];
        }else{
            $search = '';
            $order = 'id';
            $dir = 'DESC';
        }

        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder('TelepayFinancialApiBundle:Group');
        $companyQuery = $this->getRepository()->createQueryBuilder('p')
            ->orderBy('p.'.$order, $dir)
            ->where($qb->expr()->orX(
                $qb->expr()->like('p.cif', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.prefix', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.phone', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.zip', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.email', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.city', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.town', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.name', $qb->expr()->literal('%'.$search.'%'))
            ))
            ->getQuery();

        $all = $companyQuery->getResult();


        //TODO: Improve performance (two queries)
//        $all = $this->getRepository()->findBy(
//            array(),
//            array('id' => 'DESC'),
//            $limit,
//            $offset
//        );

        $filtered = $all;

        $total = count($filtered);

        foreach ($all as $group){
            $groupCreator = $group->getGroupCreator();

            $groupData = array(
                'id'    => $groupCreator->getId(),
                'name'  =>  $groupCreator->getName(),
                'allowed_methods'   =>  $groupCreator->getMethodsList()
            );
            $group = $group->getAdminView();

            $group->setGroupCreatorData($groupData);
            if($group->getMethodsList()){
                $group->setAllowedMethods($group->getMethodsList());
            }else{
                $group->setAllowedMethods(array());
            }


            $fees = $group->getCommissions();
            foreach ( $fees as $fee ){
                $currency = $fee->getCurrency();
                $fee->setScale($currency);
            }
            $limits = $group->getLimits();
            foreach ( $limits as $lim ){
                $currency = $lim->getCurrency();
                $lim->setScale($currency);
            }

        }

        $entities = array_slice($all, $offset, $limit);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities
            )
        );

    }

    /**
     * @Rest\View
     * description: return sub companies
     * permissions: ROLE_RESELLER
     */
    public function indexByCompany(Request $request){

        //todo implements reseller filter
        //list all subcompanies
        $admin = $this->get('security.context')->getToken()->getUser();
        $adminGroup = $admin->getActiveGroup();

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        //TODO: Improve performance (two queries)
        $all = $this->getRepository()->findBy(
            array('group_creator' => $adminGroup->getId()),
            array('name' => 'ASC'),
            $limit,
            $offset
        );

        $total = count($all);
        //return only the limits of active services
        foreach ($all as $group){
            $group = $group->getAdminView();
            $groupData = array(
                'id'    =>  $group->getId(),
                'name'  =>  $group->getName()
            );
            $group->setGroupCreatorData($groupData);

            $fees = $group->getCommissions();
            foreach ( $fees as $fee ){
                $currency = $fee->getCurrency();
                $fee->setScale($currency);
            }
            $limits = $group->getLimits();
            foreach ( $limits as $lim ){
                $currency = $lim->getCurrency();
                $lim->setScale($currency);
            }

        }

        $entities = array_slice($all, $offset, $limit);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities
            )
        );

    }

    /**
     * @Rest\View
     * description: create a company
     * permissions: ROLE_RESELLER(add company behind this company)
     */
    public function createAction(Request $request){

        //only the superadmin can access here
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN'))
            throw new HttpException(403, 'You have not the necessary permissions');

        $admin = $this->get('security.context')->getToken()->getUser();

        if(!$request->request->has('name')){
            throw new HttpException(400, "Parameter 'name' not found");
        }

        if(!$request->request->has('active')){
            $request->request->set('active', true);
        }

        $activeGroup = $admin->getActiveGroup();

        if(!$activeGroup->hasRole('ROLE_SUPER_ADMIN')){
            if(!$activeGroup->hasRole('ROLE_RESELLER')) throw new HttpException(403, 'Your company don\'t have the necessary permissions');
        }

        $request->request->set('roles', array('ROLE_COMPANY'));
        $request->request->set('default_currency', Currency::$EUR);
        $request->request->set('group_creator',$activeGroup);
        $request->request->set('methods_list', $activeGroup->getMethodsList());

        $group_name = $request->request->get('name');

        $resp = parent::createAction($request);

        if($resp->getStatusCode() == 201){
            $em = $this->getDoctrine()->getManager();
            $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");
            $group = $groupsRepo->findOneBy(array('name' => $group_name));

            $methodsRepo = $this->get('net.telepay.method_provider');
            $methods = $methodsRepo->findAll();

            //ya no se usa, ahora depende de los grupos.
            $adminGroup = $group->getGroupCreator();
            $groupMethodsList = $adminGroup->getMethodsList();
            foreach($methods as $method){
                if(in_array($method->getCname().'-'.$method->getType(), $groupMethodsList)){
                    //don'\t create limits because we are using tier limits

//                    $limit_def = new LimitDefinition();
//                    $limit_def->setCname($method->getCname().'-'.$method->getType());
//                    $limit_def->setSingle(0);
//                    $limit_def->setDay(0);
//                    $limit_def->setWeek(0);
//                    $limit_def->setMonth(0);
//                    $limit_def->setYear(0);
//                    $limit_def->setTotal(0);
//                    $limit_def->setGroup($group);
//                    $limit_def->setCurrency($method->getCurrency());

                    $commission = new ServiceFee();
                    $commission->setGroup($group);
                    $commission->setFixed(0);
                    $commission->setVariable(0);
                    $commission->setServiceName($method->getCname().'-'.$method->getType());
                    $commission->setCurrency($method->getCurrency());

                    $em->persist($commission);
//                    $em->persist($limit_def);
                }

            }

            $exchanges = $this->container->get('net.telepay.exchange_provider')->findAll();

            foreach($exchanges as $exchange){
                //create limit for this group
                //create fee for this group
//                    $limit = new LimitDefinition();
//                    $limit->setDay(0);
//                    $limit->setWeek(0);
//                    $limit->setMonth(0);
//                    $limit->setYear(0);
//                    $limit->setTotal(0);
//                    $limit->setSingle(0);
//                    $limit->setCname('exchange_'.$exchange->getCname());
//                    $limit->setCurrency($exchange->getCurrencyOut());
//                    $limit->setGroup($group);

                    $fee = new ServiceFee();
                    $fee->setFixed(0);
                    $fee->setVariable(0);
                    $fee->setCurrency($exchange->getCurrencyOut());
                    $fee->setServiceName('exchange_'.$exchange->getCname());
                    $fee->setGroup($group);

//                    $em->persist($limit);
                    $em->persist($fee);

            }

            //create wallets for this company
            $currencies = Currency::$ALL;
            foreach($currencies as $currency){
                $userWallet = new UserWallet();
                $userWallet->setBalance(0);
                $userWallet->setAvailable(0);
                $userWallet->setCurrency(strtoupper($currency));
                $userWallet->setGroup($group);

                $em->persist($userWallet);
            }

            $em->flush();

        }

        return $resp;
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        $user = $this->get('security.context')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        if($userGroup->hasRole('ROLE_SUPER_ADMIN') || $userGroup->getId() == $id){
            $group = $this->getRepository()->find($id);
        }else{
            $group = $this->getRepository()->findOneBy(
                array(
                    'id'        =>  $id,
                    'group_creator'   =>  $userGroup
                )
            );
        }

        if(!$group) throw new HttpException(404,'Group not found');

        $group->setAllowedMethods($group->getMethodsList());

        $fees = $group->getCommissions();
        foreach ( $fees as $fee ){
            $currency = $fee->getCurrency();
            $fee->setScale($currency);
        }
        $limits = $group->getLimits();
        foreach ( $limits as $lim ){
            $currency = $lim->getCurrency();
            $lim->setScale($currency);
        }

        $groupCreator = $group->getGroupCreator();
        $groupData = array(
            'id'    => $groupCreator->getId(),
            'name'  =>  $groupCreator->getName()
        );
        $group->setGroupCreatorData($groupData);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => 1,
                'start' => 0,
                'end' => 1,
                'elements' => $group
            )
        );
    }

    /**
     * @Rest\View
     * Permissions: ROLE_SUPER_ADMIN (all) , ROLE_RESELLER(sub-companies)
     */
    public function updateAction(Request $request, $id){

        $admin = $this->get('security.context')->getToken()->getUser();
        $adminGroup = $admin->getActiveGroup();

        $adminRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $admin->getId(),
            'group' =>  $adminGroup->getId()
        ));

        $group = $this->getRepository($this->getRepositoryName())->find($id);
        $groupCreator = $group->getGroupCreator();

        if(!$adminRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $methods = null;
        if($request->request->has('methods_list')){
            if($groupCreator->getid() != $adminGroup->getId() && !$adminRoles->hasRole('ROLE_SUPER_ADMIN'))
                throw new HttpException(403, 'You don\'t have the necessary permissions');


            $methods = $request->get('methods_list');
            $request->request->remove('methods_list');

            $tier = $group->getTier();
            $tier_methods = array(
                'sepa_in',
                'easypay_in',
                'sepa_out',
                'transfer_out'
            );

            foreach ($methods as $method){
                if(in_array($method, $tier_methods) && $tier < 2) throw new HttpException(403, 'You can\'t enable '.$method.' because the tier');
            }
        }

        $response = parent::updateAction($request, $id);

        if($response->getStatusCode() == 204){
            if($methods !== null){
                $this->_setMethods($methods, $group);
            }
        }

        return $response;

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){

        //only the superadmin can access here
//        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
//            throw new HttpException(403, 'You have not the necessary permissions');

        $admin = $this->get('security.context')->getToken()->getUser();
        $activeGroup = $admin->getActiveGroup();
        $adminRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $admin->getId(),
            'group' =>  $activeGroup->getId()
        ));

        if(!$adminRoles->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the neecssary permissions');

        $groupsRepo = $this->getDoctrine()->getRepository($this->getRepositoryName());

        $default_group = $this->container->getParameter('id_group_default');
        $level0_group = $this->container->getParameter('id_group_level_0');
        $id_group_root = $this->container->getParameter('id_group_root');

        if($id == $default_group || $id == $level0_group || $id == $id_group_root ) throw new HttpException(405, 'Not allowed');

        $group = $groupsRepo->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        if(count($group->getusers()) > 0) throw new HttpException(403, 'Not allowed. Comapny with users');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findBy(array(
            'group_id'  =>  $group->getId()
        ));

        if(count($transactions) > 0) throw new HttpException(403, 'Not allowed. Company with transactions');

        return parent::deleteAction($id);

    }

    private function _setMethods($methods, Group $group){

        $em = $this->getDoctrine()->getManager();

        $listMethods = $group->getMethodsList();

        $group->setMethodsList($methods);
        $em->persist($group);

        //get all fees and delete/create depending of methods

        $fees = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findBy(array(
            'group'  =>  $group->getId()
        ));

        $exchangeFees = array();
        foreach($fees as $fee){
            $cnameExplode = explode('_', $fee->getServiceName());
            if($cnameExplode[0] != 'exchange'){
                if(!in_array($fee->getServiceName(),$methods)){
                    $em->remove($fee);
                }
            }else{
                if(in_array($fee->getServiceName(), $exchangeFees)){
                    $em->remove($fee);
                }else{
                    $exchangeFees[] = $fee->getServiceName();
                }

            }

        }

        if(count($exchangeFees) == 0){
            //create exchange fees if not exists
            $exchanges = $this->container->get('net.telepay.exchange_provider')->findAll();

            foreach($exchanges as $exchange){
                //create fee for this group

                $fee = new ServiceFee();
                $fee->setFixed(0);
                $fee->setVariable(0);
                $fee->setCurrency($exchange->getCurrencyOut());
                $fee->setServiceName('exchange_'.$exchange->getCname());
                $fee->setGroup($group);

                $em->persist($fee);
                $em->flush();

            }
        }

        //get all limits and delete/create depending of methods
        $em = $this->getDoctrine()->getManager();
        $limits = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findBy(array(
            'group'  =>  $group->getId()
        ));

        $exchangeLimits = array();
        foreach($limits as $limit){
            $cnameExplode = explode('_', $limit->getCname());
            if($cnameExplode[0] != 'exchange'){
                if(!in_array($limit->getCname(),$methods)){
                    $em->remove($limit);
                }
            }else{
                if(in_array($limit->getCname(), $exchangeLimits)){
                    $em->remove($limit);
                }else{
                    $exchangeLimits[] = $limit->getCname();
                }

            }
        }

        if(count($exchangeLimits) == 0){
            //create exchange limits if not exists
            $exchanges = $this->container->get('net.telepay.exchange_provider')->findAll();

            foreach($exchanges as $exchange){
                //create limit for this group
                $limit = new LimitDefinition();
                $limit->setDay(0);
                $limit->setWeek(0);
                $limit->setMonth(0);
                $limit->setYear(0);
                $limit->setTotal(0);
                $limit->setSingle(0);
                $limit->setCname('exchange_'.$exchange->getCname());
                $limit->setCurrency($exchange->getCurrencyOut());
                $limit->setGroup($group);

                $em->persist($limit);
                $em->flush();

            }
        }

        //get all limitCount and delete/create depending of methods
        $em = $this->getDoctrine()->getManager();
        $limitCounts = $em->getRepository('TelepayFinancialApiBundle:LimitCount')->findBy(array(
            'group'  =>  $group->getId()
        ));

        $exchangeLimitCounts = array();
        foreach($limitCounts as $limitCount){
            $cnameExplode = explode('_', $limitCount->getCname());
            if($cnameExplode[0] != 'exchange'){
                if(!in_array($limitCount->getCname(),$methods)){
                    $em->remove($limitCount);
                }
            }else{
                if(in_array($limitCount->getCname(), $exchangeLimitCounts)){
                    $em->remove($limitCount);
                }else{
                    $exchangeLimitCounts[] = $limitCount->getCname();
                }

            }
        }

        //add new fees limits limitCounts for this methods
        foreach($methods as $method){

            $methodExplode = explode('-',$method);
            //get method config
            $methodConfig = $this->get('net.telepay.'.$methodExplode[1].'.'.$methodExplode[0].'.v1');
            $fee = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findOneBy(array(
                'group'  =>  $group->getId(),
                'service_name'  =>  $method
            ));

            if(!$fee){
                //create new ServiceFee
                $newFee = new ServiceFee();
                $newFee->setGroup($group);
                $newFee->setFixed(0);
                $newFee->setVariable(0);
                $newFee->setServiceName($method);
                $newFee->setCurrency($methodConfig->getCurrency());

                $em->persist($newFee);
            }

            //don\'t create limits because we are using tier for control limits

//            $limit = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findOneBy(array(
//                'group'  =>  $group->getId(),
//                'cname'  =>  $method
//            ));
//
//            if(!$limit){
//                //create new LimitDefinition
//                $newLimit = new LimitDefinition();
//                $newLimit->setGroup($group);
//                $newLimit->setCurrency($methodConfig->getCurrency());
//                $newLimit->setCname($method);
//                $newLimit->setDay(0);
//                $newLimit->setWeek(0);
//                $newLimit->setMonth(0);
//                $newLimit->setYear(0);
//                $newLimit->setSingle(0);
//                $newLimit->setTotal(0);
//
//                $em->persist($newLimit);
//            }

            $limitCount = $em->getRepository('TelepayFinancialApiBundle:LimitCount')->findOneBy(array(
                'group'  =>  $group->getId(),
                'cname'  =>  $method
            ));

            if(!$limitCount){
                //create new LimitCount
                $newCount = new LimitCount();
                $newCount->setDay(0);
                $newCount->setWeek(0);
                $newCount->setMonth(0);
                $newCount->setYear(0);
                $newCount->setSingle(0);
                $newCount->setTotal(0);
                $newCount->setCname($method);
                $newCount->setGroup($group);

                $em->persist($newCount);
            }
        }


        $em->flush();

        return $this->rest(204, "Edited");
    }

}
