<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use FOS\OAuthServerBundle\Model\Client;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class SwiftController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function read(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $clients = $user->getClients();
        $em = $this->getDoctrine()->getManager();
        $response = array();

        foreach ($clients as $client){
            $fees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findBy(array(
                'client'    =>  $client->getId()
            ));

            $limits = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findBy(array(
                'client'    =>  $client->getId()
            ));

            $feesCollection = array();
            foreach($fees as $fee){
                $feesCollection[] = array(
                    'id'    =>  $fee->getId(),
                    'cname' =>  $fee->getCname(),
                    'currency'  =>  $fee->getCurrency(),
                    'fixed' =>  $fee->getFixed(),
                    'variable'  =>  $fee->getVariable()
                );
            }

            $limitsCollection = array();
            foreach($limits as $limit){
                $limitsCollection[] = array(
                    'id'    =>  $limit->getId(),
                    'cname' =>  $limit->getCname(),
                    'single' =>  $limit->getSingle(),
                    'day'  =>  $limit->getDay(),
                    'week'  =>  $limit->getWeek(),
                    'month'  =>  $limit->getMonth(),
                    'year'  =>  $limit->getYear(),
                    'total'  =>  $limit->getTotal(),
                );
            }

            $response[] = array(
                'id'    =>  $client->getId(),
                'random_id' =>  $client->getRandomId(),
                'secret'    =>  $client->getSecret(),
                'name'  =>  $client->getName(),
                'fees'  =>  $feesCollection,
                'limits'    =>  $limitsCollection
            );
        }

        return $this->restV2(200, "ok", "Swift info got successfully", $response);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id=null){

        return parent::updateAction($request, $id);

    }

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Client";
    }

    function getNewEntity()
    {
        return new Client();
    }

}