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

        $request->request->add(array(
            'allowed_grant_types' => array('client_credentials'),
            'swift_list'    =>  array()
        ));

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

        $em = $this->getDoctrine()->getManager();
        if($request->request->has('user')){
            $user_id = $request->request->get('user');
            $request->request->remove('user');

            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);
            $request->request->add(array(
                'user'  =>  $user
            ));
        }

        $services = null;
        if($request->request->has('services')){
            $services = $request->get('services');
            $request->request->remove('services');
            $request->request->add(array('swift_list' =>$services));
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
    public function updateLimits(Request $request, $id){

        $em = $this->getDoctrine()->getManager();

        $limit = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->find($id);

        if($request->request->has('single')){
            $limit->setSingle($request->request->get('single'));

        }

        if($request->request->has('day')){
            $limit->setDay($request->request->get('day'));

        }

        if($request->request->has('week')){
            $limit->setWeek($request->request->get('week'));

        }

        if($request->request->has('month')){
            $limit->setMonth($request->request->get('month'));

        }

        if($request->request->has('year')){
            $limit->setYear($request->request->get('year'));

        }

        if($request->request->has('total')){
            $limit->setTotal($request->request->get('total'));

        }

        $em->persist($limit);
        $em->flush();

        return $this->restV2(204,"ok", "Updated successfully");


    }





}
