<?php

namespace App\FinancialApiBundle\Controller\Management\Manager;

use App\FinancialApiBundle\Entity\UserGroup;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;
use App\FinancialApiBundle\Entity\Fee;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Limit;
use App\FinancialApiBundle\Entity\LimitCount;
use App\FinancialApiBundle\Entity\LimitDefinition;
use App\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package App\FinancialApiBundle\Controller\Manager
 */
class GroupsController extends BaseApiController
{
    function getRepositoryName()
    {
        return "FinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }

    /**
     * @Rest\View
     * description: returns all groups
     * permissions: ROLE_SUPER_ADMIN ( all)
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request){
        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 100;
        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;
        //only the superadmin can access here
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN'))
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
        $qb = $em->createQueryBuilder('FinancialApiBundle:Group');
        $companyQuery = $this->getRepository()->createQueryBuilder('p')
            ->orderBy('p.'.$order, $dir)
            ->where($qb->expr()->orX(
                $qb->expr()->like('p.cif', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.phone', $qb->expr()->literal('%'.$search.'%')),
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
                'elements' => $entities
            )
        );
    }

    /**
     * @Rest\View
     * description: returns all groups
     * permissions: ROLE_SUPER_ADMIN ( all)
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexV2Action(Request $request){

        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);

        //only the superadmin can access here
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $search = $request->query->get("search", "");
        $sort = $request->query->getAlnum("sort", "id");
        $order = $request->query->getAlpha("order", "DESC");
        $active = $request->query->getAlnum("active", "");
        $type = $request->query->getAlpha("type", "");

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();


        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $like = $qb->expr()->orX(
            $qb->expr()->like("acc.cif", $qb->expr()->literal('%' . $search. '%')),
            $qb->expr()->like("acc.phone", $qb->expr()->literal('%' . $search. '%')),
            $qb->expr()->like("acc.name", $qb->expr()->literal('%' . $search. '%'))
        );

        $and = $qb->expr()->andX();
        $and->add($like);
        $and->add($qb->expr()->like("acc.active", $qb->expr()->literal('%' . $active. '%')));
        $and->add($qb->expr()->like("acc.type", $qb->expr()->literal('%' . $type. '%')));


        $qb = $qb->from(Group::class, 'acc')->where($and);




        $total = $qb
            ->select('count(acc.id)')
            ->getQuery()
            ->getScalarResult();

        $result = $qb
            ->select('acc')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('acc.' . $sort, $order)
            ->getQuery()
            ->getResult();


        /** @var Group $group */
        foreach ($result as $group){
            $group = $group->getAdminView();
            if($group->getMethodsList()){
                $group->setAllowedMethods($group->getMethodsList());
            }else{
                $group->setAllowedMethods(array());
            }

            $fees = $group->getCommissions();

            /** @var Fee $fee */
            foreach ( $fees as $fee ){
                $currency = $fee->getCurrency();
                $fee->setScale($currency);
            }

            $limits = $group->getLimits();
            /** @var Limit $lim */
            foreach ( $limits as $lim ){
                $currency = $lim->getCurrency();
                $lim->setScale($currency);
            }
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => intval($total[0][1]),
                'elements' => $result
            )
        );

    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        if($userGroup->hasRole('ROLE_SUPER_ADMIN') || $userGroup->getId() == $id){
            $group = $this->getRepository()->find($id);
        }else{
            $group = $this->getRepository()->findOneBy(
                array(
                    'id'        =>  $id
                )
            );
        }

        if(!$group) throw new HttpException(404,'Group not found');
        $methods = $this->get('net.app.method_provider')->findByTier($group->getTier());

        $limit_configuration = array();
        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine_mongodb')->getManager();
        foreach ($methods as $method){

            $tier_limit = $em->getRepository('FinancialApiBundle:TierLimit')->findOneBy(array(
                'method'    =>  $method->getCname().'-'.$method->getType(),
                'tier'  =>  $group->getTier()
            ));

            if(!$tier_limit) throw new HttpException('403', $method->getCname().'-'.$method->getType());
            $total_last_day = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 1);
            $total_last_month = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 30);

            $lim = array(
                'method'    =>  $method,
                'month_limit'     =>  $tier_limit->getMonth(),
                'month_spent'   =>  $total_last_month[0]['total'] ? $total_last_month[0]['total']:0,
                'day_limit' =>  $tier_limit->getDay(),
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

        $admin = $this->get('security.token_storage')->getToken()->getUser();
        $adminGroup = $admin->getActiveGroup();

        $adminRoles = $this->getDoctrine()->getRepository('FinancialApiBundle:UserGroup')->findOneBy(array(
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
//        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN'))
//            throw new HttpException(403, 'You have not the necessary permissions');

        $admin = $this->get('security.token_storage')->getToken()->getUser();
        $activeGroup = $admin->getActiveGroup();
        $adminRoles = $this->getDoctrine()->getRepository('FinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $admin->getId(),
            'group' =>  $activeGroup->getId()
        ));

        if(!$adminRoles->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the neecssary permissions');
        $groupsRepo = $this->getDoctrine()->getRepository($this->getRepositoryName());
        $id_group_root = $this->container->getParameter('id_group_root');
        if($id == $id_group_root ) throw new HttpException(405, 'Not allowed');

        $group = $groupsRepo->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        if(count($group->getusers()) > 0) throw new HttpException(403, 'Not allowed. Comapny with users');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transactions = $dm->getRepository('FinancialApiBundle:Transaction')->findBy(array(
            'group_id'  =>  $group->getId()
        ));

        if(count($transactions) > 0) throw new HttpException(403, 'Not allowed. Company with transactions');

        return parent::deleteAction($id);

    }

    /**
     * @Rest\View
     */
    public function indexByCompany(Request $request, $id){
        $em = $this->getDoctrine()->getManager();

        //only the superadmin can access here
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $listGroups = $em->getRepository(UserGroup::class)->findBy(['user'  =>  $id]);

        $listData = [];
        foreach($listGroups as $group) {
            $company = $em->getRepository(Group::class)->findOneBy(['id'  => $group->getGroup()]);

            if (!$company) throw new HttpException(404, 'Company not found');
            $listData[] = array(
                "id" => $company->getId(),
                "user_roles" => $group->getRoles(),
                "name" => $company->getName(),
                "email" => $company->getEmail(),
                "active" => $company->getActive(),
                "description" => $company->getDescription(),
                "public_image" => $company->getPublicImage(),
                "company_image" => $company->getCompanyImage(),
                "longitude" => $company->getLongitude(),
                "latitude" => $company->getLatitude(),
                "web" => $company->getWeb(),
                "type" => $company->getType(),
                "subtype" => $company->getSubtype(),
                "country" => $company->getCountry(),
                "city" => $company->getCity(),
                "street_type" => $company->getStreetType(),
                "street" => $company->getStreet(),
                "street_number" => $company->getAddressNumber(),
                "prefix" => $company->getPrefix(),
                "phone_number" => $company->getPhone(),
                "cif" => $company->getCif(),
                "rec_address" => $company->getRecAddress()
            );
        }

        return $this->rest(
            200,
            "Request successful",
            array(
                'total' => count($listData),
                'start' => 0,
                'end' => count($listGroups)-1,
                'elements' => $listData
            )
        );
    }

    private function _setMethods($methods, Group $group){

        $em = $this->getDoctrine()->getManager();

        $listMethods = $group->getMethodsList();

        $group->setMethodsList($methods);
        $em->persist($group);

        //get all fees and delete/create depending of methods

        $fees = $em->getRepository('FinancialApiBundle:ServiceFee')->findBy(array(
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
            $exchanges = $this->container->get('net.app.exchange_provider')->findAll();

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
        $limits = $em->getRepository('FinancialApiBundle:LimitDefinition')->findBy(array(
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
            $exchanges = $this->container->get('net.app.exchange_provider')->findAll();

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
        $limitCounts = $em->getRepository('FinancialApiBundle:LimitCount')->findBy(array(
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
            $methodConfig = $this->get('net.app.'.$methodExplode[1].'.'.$methodExplode[0].'.v1');
            $fee = $em->getRepository('FinancialApiBundle:ServiceFee')->findOneBy(array(
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

            $limitCount = $em->getRepository('FinancialApiBundle:LimitCount')->findOneBy(array(
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
