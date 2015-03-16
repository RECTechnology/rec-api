<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Doctrine\DBAL\DBALException;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\AccessToken;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
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
        return parent::indexAction($request);
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){

        $admin=$this->get('security.context')->getToken()->getUser();

        $request->request->set('roles',array('ROLE_USER'));
        $request->request->set('creator',$admin);

        $group_name=$request->request->get('name');

        $resp = parent::createAction($request);

        if($resp->getStatusCode() == 201){
            $em=$this->getDoctrine()->getManager();
            $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");
            $group = $groupsRepo->findOneBy(array('name' => $group_name));

            $servicesRepo = $this->get('net.telepay.service_provider');
            $services = $servicesRepo->findAll();

            foreach($services as $service){
                $limit_def = new LimitDefinition();
                $limit_def->setCname($service->getCname());
                $limit_def->setSingle(0);
                $limit_def->setDay(0);
                $limit_def->setWeek(0);
                $limit_def->setMonth(0);
                $limit_def->setYear(0);
                $limit_def->setTotal(0);
                $limit_def->setGroup($group);
                $commission = new ServiceFee();
                $commission->setGroup($group);
                $commission->setFixed(0);
                $commission->setVariable(0);
                $commission->setServiceName($service->getCname());
                $em->persist($commission);
                $em->persist($limit_def);

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

        $groupsRepo=$this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");

        $group = $groupsRepo->find($id);

        if($group->getName()=='Default') throw new HttpException(400,"This group can't be deleted.");

        if(count($group->getUsers())>0) throw new HttpException(400,"This group can't be deleted because has users.");

        return parent::deleteAction($id);
    }

}
