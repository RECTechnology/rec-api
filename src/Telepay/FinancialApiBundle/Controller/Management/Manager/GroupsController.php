<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

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
     */
    public function indexAction(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 100;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        //TODO: Improve performance (two queries)
        $all = $this->getRepository()->findAll();

        $total = count($all);

        foreach ($all as $group){
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
    public function indexByUser(Request $request){

        $admin = $this->get('security.context')->getToken()->getUser();

        if (!$admin) throw new HttpException(404, 'Not user found');

        $roles = $admin->getRoles();

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        //TODO: Improve performance (two queries)
        $all = $this->getRepository()->findBy(array(
            'creator'   =>  $admin->getId()
        ));

        $total = count($all);
        //return only the limits of active services
        foreach ($all as $group){
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
    public function createAction(Request $request){

        $admin = $this->get('security.context')->getToken()->getUser();

        $request->request->set('roles',array('ROLE_USER'));
        $request->request->set('creator',$admin);

        $group_name = $request->request->get('name');

        $resp = parent::createAction($request);

        if($resp->getStatusCode() == 201){
            $em = $this->getDoctrine()->getManager();
            $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");
            $group = $groupsRepo->findOneBy(array('name' => $group_name));

            $servicesRepo = $this->get('net.telepay.service_provider');
            $services = $servicesRepo->findAll();

            $admin = $group->getCreator();
            $servicesList = $admin->getServicesList();
            foreach($services as $service){
                if(in_array($service->getCname(),$servicesList)){
                    $limit_def = new LimitDefinition();
                    $limit_def->setCname($service->getCname());
                    $limit_def->setSingle(0);
                    $limit_def->setDay(0);
                    $limit_def->setWeek(0);
                    $limit_def->setMonth(0);
                    $limit_def->setYear(0);
                    $limit_def->setTotal(0);
                    $limit_def->setGroup($group);
                    $limit_def->setCurrency($service->getCurrency());
                    $commission = new ServiceFee();
                    $commission->setGroup($group);
                    $commission->setFixed(0);
                    $commission->setVariable(0);
                    $commission->setServiceName($service->getCname());
                    $commission->setCurrency($service->getCurrency());
                    $em->persist($commission);
                    $em->persist($limit_def);
                }

            }

            $exchanges = $this->getContainer()->get('net.telepay.exchange_provider')->findAll();

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
                    $limit->setCname($exchange->getCname());
                    $limit->setCurrency($exchange->getCurrencyOut());
                    $limit->setGroup($group);

                    $fee = new ServiceFee();
                    $fee->setFixed(0);
                    $fee->setVariable(0);
                    $fee->setCurrency($exchange->getCurrencyOut());
                    $fee->setServiceName($exchange->getCname());
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
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){

        $groupsRepo = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");

        $default_group = $this->container->getParameter('id_group_default');
        $level0_group = $this->container->getParameter('id_group_level_0');

        if($id == $default_group || $id == $level0_group ) throw new HttpException(405, 'Not allowed');

        $group = $groupsRepo->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        if($group->getName() == 'Default') throw new HttpException(405,"This group can't be deleted.");

        if(count($group->getUsers()) > 0) throw new HttpException(405,"This group can't be deleted because has users.");

        return parent::deleteAction($id);

    }


}
