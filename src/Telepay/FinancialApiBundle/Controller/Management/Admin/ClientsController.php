<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Client;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\SwiftFee;
use Telepay\FinancialApiBundle\Entity\SwiftLimit;
use WebSocket\Exception;

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
            foreach($services as $service){
                $method = explode('-',$service,2);

                $exist_method_in = $this->get('net.telepay.swift_provider')->isValidMethod($method[0]);

                if($exist_method_in == false){
                    throw new HttpException(404, 'Cash in method '.$method[0].' not found');
                }else{
                    $method_in = $this->get('net.telepay.swift_provider')->findByCname($method[0]);
                    if($method_in->getType() != 'cash_in') throw new HttpException(404, 'Cash in method '.$method[0].' not found');
                }

                if(!isset($method[1]) ) throw new HttpException(404, 'Cash out method not found');

                $exist_method_out = $this->get('net.telepay.swift_provider')->isValidMethod($method[1]);

                if($exist_method_out == false){
                    throw new HttpException(404, 'Cash out method '.$method[1].' not found');
                }else{
                    $method_out = $this->get('net.telepay.swift_provider')->findByCname($method[1]);
                    if($method_out->getType() != 'cash_out') throw new HttpException(404, 'Cash out method '.$method[1].' not found');
                }

            }
            $request->request->remove('services');
            $request->request->add(array('swift_list' =>$services));
        }

        $response = parent::updateAction($request, $id);

        if($response->getStatusCode() == 204){
            $client = $em->getRepository('TelepayFinancialApiBundle:Client')->find($id);
            $this->_createLimitsFees($client, $services);
        }

        return $response;
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

    private function _createLimitsFees(Client $client, $services){

        $em = $this->getDoctrine()->getManager();
        foreach($services as $service){
            $limit = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findOneBy(array(
                'client' =>  $client->getId(),
                'cname' =>  $service
            ));

            $types = preg_split('/-/', $service, 2);
            $cashOutMethod = $this->container->get('net.telepay.out.'.$types[1].'.v1');

            if(!$limit){
                $limit = new SwiftLimit();
                $limit = $limit->createFromController($service, $client);
                $limit->setCurrency($cashOutMethod->getCurrency());
                $em->persist($limit);
                $em->flush();
            }

            $fee = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
                'client' =>  $client->getId(),
                'cname' =>  $service
            ));

            if(!$fee){
                $fee = new SwiftFee();
                $fee = $fee->createFromController($service, $client);
                $fee->setCurrency($cashOutMethod->getCurrency());
                $em->persist($fee);
                $em->flush();

            }
        }

    }

}
