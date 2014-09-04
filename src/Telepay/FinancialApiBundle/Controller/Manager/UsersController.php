<?php

namespace Telepay\FinancialApiBundle\Controller\Manager;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\Service;
use Telepay\FinancialApiBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UsersController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class UsersController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:User";
    }

    function getNewEntity()
    {
        return new User();
    }



    /**
     * @Rest\View
     */
    public function indexAction(Request $request){
        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $all = $this->getRepository()->findAll();

        $total = count($all);

        $entities = array_slice($all, $offset, $limit);
        array_map(function($elem){
            $services = $elem->getAllowedServices();
            $elem->setAllowedServices($services);
            $elem->setAccessToken(null);
            $elem->setRefreshToken(null);
            $elem->setAuthCode(null);
        }, $entities);

        return $this->handleRestView(
            200,
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
        if(!$request->request->has('password'))
            throw new HttpException(400, "Missing parameter 'password'");
        $password = $request->get('password');
        $request->request->remove('password');
        $request->request->add(array('plain_password'=>$password));
        return parent::createAction($request);
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entities = $repo->findOneBy(array('id'=>$id));

        if(empty($entities)) throw new HttpException(404, "Not found");

        $services = $entities->getAllowedServices();
        $entities->setAllowedServices($services);
        $entities->setAccessToken(null);
        $entities->setRefreshToken(null);
        $entities->setAuthCode(null);
        return $this->handleRestView(200, "Request successful", $entities);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        if($request->request->has('password')){
            $password = $request->get('password');
            $request->request->remove('password');
            $request->request->add(array('plain_password'=>$password));
        }
        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }



    /**
     * @Rest\View
     */
    public function addService(Request $request, $id){
        $serviceId = $request->get('service_id');
        if(empty($serviceId)) throw new HttpException(400, "Missing parameter 'service_id'");
        $usersRepo = $this->getRepository();
        $servicesRepo = new ServicesRepository();
        $user = $usersRepo->findOneBy(array('id'=>$id));
        $service = $servicesRepo->findById($serviceId);
        if(empty($user)) throw new HttpException(404, 'User not found');
        if(empty($service)) throw new HttpException(404, 'Service not found');
        if($user->hasRole($service->getRole())) throw new HttpException(409, "User has already the service '$serviceId'");

        $user->addRole($service->getRole());
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);

        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }

        return $this->handleRestView(201, "Service added successfully", array());

    }

    /**
     * @Rest\View
     */
    public function deleteService($id, $service_id){
        $usersRepo = $this->getRepository();
        $servicesRepo = new ServicesRepository();
        $service = $servicesRepo->findById($service_id);

        $user = $usersRepo->findOneBy(array('id'=>$id));
        if(empty($user)) throw new HttpException(404, "User not found");
        if(!$user->hasRole($service->getRole())) throw new HttpException(404, "Service not found in specified user");

        $user->removeRole($service->getRole());

        $em = $this->getDoctrine()->getManager();

        $em->persist($user);
        $em->flush();

        return $this->handleRestView(
            204,
            "Service deleted from user successfully",
            array()
        );
    }


}
