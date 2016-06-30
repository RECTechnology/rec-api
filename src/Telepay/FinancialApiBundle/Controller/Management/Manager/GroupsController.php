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

        //TODO: Improve performance (two queries)
        $all = $this->getRepository()->findAll();

        $total = count($all);
        foreach ($all as $group){
            $groupCreator = $group->getGroupCreator();
            $groupData = array(
                'id'    => $groupCreator->getId(),
                'name'  =>  $groupCreator->getName()
            );
            $group = $group->getAdminView();
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
        $all = $this->getRepository()->findBy(array(
            'group_creator'   =>  $adminGroup->getId()
        ));

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

        $activeGroup = $admin->getActiveGroup();

        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')){
            if($activeGroup->hasRole('ROLE_RESELLER')) throw new HttpException(403, 'Your company don\'t have the necessary permissions');
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
                    $limit_def = new LimitDefinition();
                    $limit_def->setCname($method->getCname().'-'.$method->getType());
                    $limit_def->setSingle(0);
                    $limit_def->setDay(0);
                    $limit_def->setWeek(0);
                    $limit_def->setMonth(0);
                    $limit_def->setYear(0);
                    $limit_def->setTotal(0);
                    $limit_def->setGroup($group);
                    $limit_def->setCurrency($method->getCurrency());

                    $commission = new ServiceFee();
                    $commission->setGroup($group);
                    $commission->setFixed(0);
                    $commission->setVariable(0);
                    $commission->setServiceName($method->getCname().'-'.$method->getType());
                    $commission->setCurrency($method->getCurrency());

                    $em->persist($commission);
                    $em->persist($limit_def);
                }

            }

            $exchanges = $this->container->get('net.telepay.exchange_provider')->findAll();

            foreach($exchanges as $exchange){
                //create limit for this group
                //create fee for this group
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

                    $fee = new ServiceFee();
                    $fee->setFixed(0);
                    $fee->setVariable(0);
                    $fee->setCurrency($exchange->getCurrencyOut());
                    $fee->setServiceName('exchange_'.$exchange->getCname());
                    $fee->setGroup($group);

                    $em->persist($limit);
                    $em->persist($fee);

            }

            $em->flush();

        }

        return $resp;
    }

    /**
     * @Rest\View
     */
    public function showAction($id){

        $admin = $this->get('security.context')->getToken()->getUser();
        $adminGroup = $admin->getActiveGroup();

        //TODO: Improve performance (two queries)
        if($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')){
            $group = $this->getRepository()->find($id);
        }else{
            $group = $this->getRepository()->findOneBy(
                array(
                    'id'        =>  $id,
                    'group_creator'   =>  $adminGroup
                )
            );
        }

        if(!$group) throw new HttpException(404,'Group not found');

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
     */
    public function updateAction(Request $request, $id){
        //check that this user is the creator of this group or is the superadmin
        //only the superadmin can access here
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN'))
            throw new HttpException(403, 'You have not the necessary permissions');

        $user = $this->get('security.context')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        $group = $this->getRepository($this->getRepositoryName())->find($id);
        $groupCreator = $group->getGroupCreator();

        if($groupCreator->getid() != $userGroup->getId() && !$user->hasRole('ROLE_SUPER_ADMIN'))
            throw new HttpException(409, 'You don\'t have the necessary permissions');

        $methods = null;
        if($request->request->has('methods_list')){
            $methods = $request->get('methods_list');
        }

        $response = parent::updateAction($request, $id);

        if($response->getStatusCode() == 204){
            if($methods !== null){
                $this->_setMethods($request, $id);
            }
        }

        return $response;

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){

        //only the superadmin can access here
        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You have not the necessary permissions');

        $user = $this->get('security.context')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();
        $groupsRepo = $this->getDoctrine()->getRepository($this->getRepositoryName());

        $default_group = $this->container->getParameter('id_group_default');
        $level0_group = $this->container->getParameter('id_group_level_0');
        $id_group_root = $this->container->getParameter('id_group_root');

        if($id == $default_group || $id == $level0_group || $id == $id_group_root ) throw new HttpException(405, 'Not allowed');

        $group = $groupsRepo->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        if($group->getGroupCreator() != $userGroup) throw new HttpException(403, 'You do not have the necessary permissions');

        if($group->getName() == 'Default') throw new HttpException(405,"This group can't be deleted.");

        if(count($group->getUsers()) > 0) throw new HttpException(405,"This group can't be deleted because has users.");

        return parent::deleteAction($id);

    }

    private function _setMethods(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        $groupsRepo = $this->getRepository();
        $group = $groupsRepo->findOneBy(array('id'=>$id));
        $listMethods = $group->getMethodsList();

        $putMethods = $request->get('methods_list');
        foreach($putMethods as $method){
            if(!in_array($method, $listMethods)){
                $this->_addMethod($id, $method);
            }
        }
        return $this->rest(204, "Edited");
    }

    private function _addMethod($id, $cname){
        $groupsRepo = $this->getRepository();
        $methodsRepo = $this->get('net.telepay.method_provider');
        $group = $groupsRepo->findOneBy(array('id'=>$id));
        $method = $methodsRepo->findByCname($cname);
        if(empty($group)) throw new HttpException(404, 'User not found');
        if(empty($method)) throw new HttpException(404, 'Method not found');

        $group->addMethod($cname);
        $em = $this->getDoctrine()->getManager();
        $limitRepo = $em->getRepository("TelepayFinancialApiBundle:LimitCount");
        $limit = $limitRepo->findOneBy(array('cname' => $cname, 'group' => $group));

        if(!$limit){
            $limit = new LimitCount();
            $limit->setGroup($group);
            $limit->setCname($cname);
            $limit->setSingle(0);
            $limit->setDay(0);
            $limit->setWeek(0);
            $limit->setMonth(0);
            $limit->setYear(0);
            $limit->setTotal(0);
            $em->persist($limit);
        }

        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }
    }

}
