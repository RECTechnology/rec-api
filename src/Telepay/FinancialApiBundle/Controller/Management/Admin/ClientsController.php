<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Client;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\SwiftLimit;

/**
 * Class ClientController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class ClientsController extends BaseApiController {

    function getRepositoryName() {
        return 'TelepayFinancialApiBundle:Client';
    }

    function getNewEntity() {
        return new Client();
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

        $em = $this->getDoctrine()->getManager();

        if($request->request->has('user')){
            $user_id = $request->request->get('user');
            $request->request->remove('user');
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);

        }else{
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find(1);
        }

        if(!$user) throw new HttpException(404, 'User not found');

        $request->request->add(array('allowed_grant_types' => array('client_credentials')));

        $response = parent::createAction($request);

        if($response->getStatusCode() == 201){
            $content = json_decode($response->getContent());
            $client_id = $content->data->id;
            $client = $em->getRepository('TelepayFinancialApiBundle:Client')->find($client_id);
            //add user and create limits and fees
            $client->setUser($user);
            $em->persist($client);
            $em->flush();

            //TODO create limits and fees foreach swift methods

        }

        return $response;
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

        if($request->request->has('user')){
            $user_id = $request->request->get('user');
            $request->request->remove('user');
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);
            $request->request->add(array(
                'user'  =>  $user
            ));
        }

        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }


}
