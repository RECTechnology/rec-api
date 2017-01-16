<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Entity\Client;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\SwiftFee;
use Telepay\FinancialApiBundle\Entity\SwiftLimit;
use Telepay\FinancialApiBundle\Financial\Currency;
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

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;


        $groupRepo = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:Client');
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder('TelepayFinancialApiBundle:Client');

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

        $clientQuery = $groupRepo->createQueryBuilder('p')
            ->orderBy('p.'.$order, $dir)
            ->where($qb->expr()->orX(
                $qb->expr()->like('p.id', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.swift_list', $qb->expr()->literal('%'.$search.'%')),
                $qb->expr()->like('p.name', $qb->expr()->literal('%'.$search.'%'))
            ))
            ->getQuery();

        $all = $clientQuery->getResult();

        $total = count($all);

        $entities = array_slice($all, $offset, $limit);
        array_map(function($elem){
            $group_data = array();
            $group = $elem->getGroup();
            $group_data['id'] = $group->getId();
            $group_data['name'] = $group->getName();
            $elem->setGroupData($group_data);
        }, $entities);

        return $this->rest(
            200,
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities
            )
        );
//        return parent::indexAction($request);
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.context')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        $adminRoles = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:UserGroup")->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $userGroup->getId()
            )
        );

        //check if this user is admin of this group
        if($request->request->has('group')){
            $group_id = $request->request->get('group');
            $request->request->remove('group');
            $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($group_id);

            if($company->getId() == $userGroup->getId()){
                if(!$adminRoles->hasRole('ROLE_ADMIN') || !$user->hasGroup($userGroup->getName()))
                    throw new HttpException(409, 'You don\'t have the necesary permissions in this company');
            }else{
                if(!$adminRoles->hasRole('ROLE_SUPER_ADMIN'))
                    throw new HttpException(409, 'You don\'t have the necesary permissions');
            }

        }

        if($request->request->has('allowed_grant_types')){
            $grant_types = $request->request->get('allowed_grant_types');
        }else{
            $grant_types = array('client_credentials');
        }

        $uris = $request->request->get('redirect_uris');
        $request->request->remove('redirect_uris');

        //put all swift methods available but inactive for each new client
//        $swiftMethods = $this->get('net.telepay.swift_provider')->findAll();

        $request->request->add(array(
            'allowed_grant_types' => $grant_types,
            'swift_list'    =>  '',
            'redirect_uris' => array($uris),
            'group' =>  $userGroup
        ));

        return parent::createAction($request);

//        if($response->getStatusCode() == 201){
//            $content = json_decode($response->getContent());
//            $client_id = $content->data->id;
//            $client = $em->getRepository('TelepayFinancialApiBundle:Client')->find($client_id);
//
//            //create limits and fees foreach swift methods
//            $this->_createLimitsFees($client, $swiftMethods);
//
//        }

//        return $response;
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        $repo = $this->getRepository();
        $entities = $repo->findOneBy(array('id'=>$id));
        if(empty($entities)) throw new HttpException(404, "Not found");

        $methods = array();
        $services = $this->get('net.telepay.swift_provider')->findAll();
        foreach($services as $service){
            $method = explode('-',$service);
            $method_in = $this->get('net.telepay.in.'.$method[0].'.v1');
            $method_out = $this->get('net.telepay.out.'.$method[1].'.v1');
            $method_name = $method_in->getCname().'-'.$method_out->getCname();
            $methods[$method_name]['status'] = 'inactive';
        }

        $list_methods = $entities->getSwiftList();
        $list_fees = $entities->getSwiftFees();
        $list_limits = $entities->getSwifLimits();
        foreach($list_methods as $method){
            $method =  explode(":", $method)[0];
            $methods[$method]['status'] = 'active';
        }
        foreach($list_limits as $limit){
            $cname = $limit->GetCname();
            $methods[$cname]['limit']['id']=$limit->GetId();
            $methods[$cname]['limit']['single']=$limit->GetSingle();
            $methods[$cname]['limit']['day']=$limit->GetDay();
            $methods[$cname]['limit']['week']=$limit->GetWeek();
            $methods[$cname]['limit']['month']=$limit->GetMonth();
            $methods[$cname]['limit']['year']=$limit->GetYear();
            $methods[$cname]['limit']['total']=$limit->GetTotal();
            $methods[$cname]['limit']['currency']=$limit->GetCurrency();
            $methods[$cname]['limit']['scale'] = Currency::$SCALE[$limit->GetCurrency()];
        }
        foreach($list_fees as $fee){
            $cname = $fee->GetCname();
            $methods[$cname]['fee']['id']=$fee->GetId();
            $methods[$cname]['fee']['fixed']=$fee->GetFixed();
            $methods[$cname]['fee']['variable']=$fee->GetVariable();
            $methods[$cname]['fee']['currency']=$fee->GetCurrency();
            $methods[$cname]['fee']['scale'] = Currency::$SCALE[$fee->GetCurrency()];
        }
        $resp = array(
            'entities' =>  $entities,
            'methods' =>  $methods,
        );
        return $this->restPlain(200, $resp);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        //Change owner of this client
        if($request->request->has('group')){
            $group_id = $request->request->get('group');
            $request->request->remove('group');
            $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($group_id);
            $request->request->add(array(
                'group'  =>  $group
            ));
        }

        $services = null;
        if($request->request->has('swift_list')){
            $services = $request->get('swift_list');
            foreach($services as $service){
                $method = explode('-',$service,2);
                $validSwiftMethods = $this->get('net.telepay.swift_provider')->findAll();
                if(!in_array($service, $validSwiftMethods)) throw new HttpException(404, 'Method not allowed');
                $exist_method_in = $this->get('net.telepay.method_provider')->isValidMethod($method[0].'-in');
                if($exist_method_in == false){
                    throw new HttpException(404, 'Cash in method '.$method[0].' not found');
                }else{
                    $method_in = $this->get('net.telepay.method_provider')->findByCname($method[0].'-in');
                    if($method_in->getType() != 'in') throw new HttpException(404, 'Cash in method '.$method[0].' not found');
                }

                if(!isset($method[1]) ) throw new HttpException(404, 'Cash out method not found');
                $exist_method_out = $this->get('net.telepay.method_provider')->isValidMethod($method[1].'-out');
                if($exist_method_out == false){
                    throw new HttpException(404, 'Cash out method '.$method[1].' not found');
                }else{
                    $method_out = $this->get('net.telepay.method_provider')->findByCname($method[1].'-out');
                    if($method_out->getType() != 'out') throw new HttpException(404, 'Cash out method '.$method[1].' not found');
                }
            }
        }

        if($request->request->has('redirect_uris')) {
            $uris = $request->request->get('redirect_uris');
            $request->request->set('redirect_uris', array($uris));
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
