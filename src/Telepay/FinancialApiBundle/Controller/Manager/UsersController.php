<?php

namespace Telepay\FinancialApiBundle\Controller\Manager;

use Doctrine\DBAL\DBALException;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\AccessToken;
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
    public function su(Request $request, $id){

        $em = $this->getDoctrine()->getManager();
        $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
        $tokensRepo = $em->getRepository("TelepayFinancialApiBundle:AccessToken");

        $user = $usersRepo->findOneBy(array('id'=>$id));

        $token = $tokensRepo->findOneBy(
            array('token'=>$this->get('security.context')->getToken()->getToken())
        );

        $token->setUser($user);
        //$token->setAuthenticated(true);

        $em->persist($token);
        $token2 = $tokensRepo->findOneBy(
            array('token'=>$this->get('security.context')->getToken()->getToken())
        );

        return $this->handleRestView(
            200,
            "yeagh accesstokensss",
            $token2
        );
        //update access_token -> user with id $id
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
        $securityContext = $this->get('security.context');

        if(!$securityContext->isGranted('ROLE_SUPER_ADMIN')){
            $filtered = [];
            foreach($all as $user){
                if(!$user->hasRole('ROLE_SUPER_ADMIN'))
                    $filtered []= $user;
            }
        }
        else{
            $filtered = $all;
        }





        $total = count($filtered);

        $entities = array_slice($filtered, $offset, $limit);
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
        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
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
    public function setImage(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        if($request->request->has('base64_image')) $base64Image = $request->request->get('base64_image');
        else throw new HttpException(400, "Missing parameter 'base64_image'");


        $image = base64_decode($base64Image);

        try {
            imagecreatefromstring($image);
        }catch (Exception $e){
            throw new HttpException(400, "Invalid parameter 'base64_image'");
        }

        $repo = $this->getRepository();

        $user = $repo->findOneBy(array('id'=>$id));

        if(empty($user)) throw new HttpException(404, "User Not found");

        $user->setBase64Image($base64Image);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);

        try{
            $em->flush();
            return $this->handleRestView(
                204,
                "Image changed successfully"
            );
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }


    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        $services = null;
        if($request->request->has('services')){
            $services=$request->get('services');
            $request->request->remove('services');
        }
        $role = null;
        if($request->request->has('role')){
            $role=$request->get('role');
            $request->request->remove('role');
        }
        if($request->request->has('password')){
            $userManager = $this->container->get('access_key.security.user_provider');
            $user = $userManager->loadUserById($id);
            $user->setPlainPassword($request->get('password'));
            $userManager->updatePassword($user);
            $request->request->remove('password');
        }
        $resp = parent::updateAction($request, $id);
        if($resp->getStatusCode() == 204){
            if($services !== null){
                $request->request->add(array('services'=>$services));
                $this->_setServices($request, $id);
            }
            if($role !== null){
                $request->request->add(array('role'=>$role));
                $this->_setRole($request, $id);
            }
        }
        return $resp;
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }


    private function _setServices(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $putServices=array();
        if(trim($request->get('services')) != "")
            $putServices = explode(" ",$request->get('services'));
        $admin=$this->getUser();
        foreach($admin->getAllowedServices() as $adminService){
            try{
                $this->_deleteService($id, $adminService->getId());
            }catch(HttpException $e){
                if($e->getStatusCode() != 404){
                    throw $e;
                }
            }
        }
        foreach($putServices as $service){
            $this->_addService($id, $service);
        }

        return $this->handleRestView(204, "Edited");
    }


    /**
     * @Rest\View
     */
    public function addService(Request $request, $id){
        $serviceId = $request->get('service_id');
        if(empty($serviceId)) throw new HttpException(400, "Missing parameter 'service_id'");
        $this->_addService($id, $serviceId);
        return $this->handleRestView(201, "Service added successfully", array());
    }

    /**
     * @Rest\View
     */
    public function deleteService($id, $service_id){
        $this->_deleteService($id, $service_id);
        return $this->handleRestView(
            204,
            "Service deleted from user successfully"
        );
    }


    private function _addService($id, $serviceId){
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

    }

    private function _deleteService($id, $service_id){
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
    }


    public function _setRole(Request $request, $id){
        $roleName = $request->get('role');

        if(empty($roleName))
            throw new HttpException(400, "Missing parameter 'role'");
        if($roleName != 'ROLE_SUPER_ADMIN' and $roleName != 'ROLE_ADMIN' and $roleName != 'ROLE_USER'){
            throw new HttpException(404, 'Role not found');
        }

        $usersRepo = $this->getRepository();
        $user = $usersRepo->findOneBy(array('id'=>$id));
        if(empty($user))
            throw new HttpException(404, 'User not found');

        $user->removeRole('ROLE_SUPER_ADMIN');
        $user->removeRole('ROLE_ADMIN');

        if($roleName == 'ROLE_SUPER_ADMIN' or $roleName == 'ROLE_ADMIN')
            $user->addRole($roleName);
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

    }



}
