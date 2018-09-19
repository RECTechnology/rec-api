<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;
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
        $filtered = $all;
        $total = count($filtered);
        foreach ($all as $group){
            $group = $group->getAdminView();
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
        $methods = $this->get('net.telepay.method_provider')->findByTier($group->getTier());

        $limit_configuration = array();
        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine_mongodb')->getManager();
        foreach ($methods as $method){

            $tier_limit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                'method'    =>  $method->getCname().'-'.$method->getType(),
                'tier'  =>  $group->getTier()
            ));

            if(!$tier_limit) throw new HttpException('403', $method->getCname().'-'.$method->getType());
            $total_last_day = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 1);
            $total_last_month = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 30);

            $lim = array(
                'method'    =>  $method,
                'month_limit'     =>  $tier_limit->getMonth(),
                'month_spent'   =>  $total_last_month[0]['total'] ? $total_last_month[0]['total']:0,
                'day_limit' =>  $tier_limit->getDay(),
                'day_spent' =>  $total_last_day[0]['total'] ? $total_last_day[0]['total']:0
            );

            $limit_configuration[] = $lim;
        }

        //TODO search exchange methods by tier
        $exchange_limits = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->createQueryBuilder('e')
            ->where('e.tier = :tier')
            ->andWhere('e.method LIKE :method')
            ->setParameter('tier', $group->getTier())
            ->setParameter('method', 'exchange%')
            ->getQuery()
            ->getResult();

        foreach ($exchange_limits as $exchange_limit){

            $exchange_currency = explode('_', $exchange_limit->getMethod());
            $exchange_method = new AbstractMethod(
                $exchange_limit->getMethod(),
                strtolower($exchange_limit->getMethod()),
                'exchange',
                $exchange_currency[1],
                false,
                '',
                '',
                $group->getTier()
            );

            $total_last_day = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $exchange_currency[1], 1);
            $total_last_month = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $exchange_currency[1], 30);

            $lim = array(
                'method'    =>  $exchange_method,
                'month_limit'     =>  $exchange_limit->getMonth(),
                'month_spent'   =>  $total_last_month[0]['total'] ? $total_last_month[0]['total']:0,
                'day_limit' =>  $exchange_limit->getDay(),
                'day_spent' =>  $total_last_day[0]['total'] ? $total_last_day[0]['total']:0
            );

            $limit_configuration[] = $lim;

        }

        $group->setLimitConfiguration($limit_configuration);
        $group->setAllowedMethods($methods);

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
        if(!$adminRoles->isSuperadmin()) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $methods = null;
        if($request->request->has('methods_list')){
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

        if($request->request->has('roles')){
            $roles = $request->request->get('roles');
            if(in_array('ROLE_SUPER_ADMIN', $roles)) throw new HttpException(403, 'Bad parameters');
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
