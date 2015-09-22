<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\CashInTokens;

class CashInController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function indexAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $all = $this->getRepository()->findBy(array(
            'user'  =>  $user
        ));

        $total = count($all);

        $filtered = [];
        foreach($all as $tpv){
            $filtered[] = $tpv->getTpvView();
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $filtered
            )
        );
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
    public function createAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        if(!$request->request->has('service')) throw new HttpException(404, 'Bad parameters');

        $service = $request->request->get('service');

        //check if the service is allowed
        $services = $user->getServicesList();

        if(!in_array($service, $services)) throw new HttpException(405, 'Service not allowed');

        //check if is created yet because only one per service is allowed

        $em = $this->getDoctrine()->getManager();
        $actual_token = $em->getRepository('TelepayFinancialApiBundle:CashInTokens')->findOneBy(array(
            'user'  =>  $user,
            'service'   =>  $service
        ));

        if($actual_token) throw new HttpException(409, 'Duplicated resource');

        $token = $this->getReference();
        $request->request->add(array(
            'token'    =>  $token,
            'user'   =>  $user
        ));

        $response = parent::createAction($request);

        $data = $response->getContent();
        $data = json_decode($data);
        $data = $data->data;
        $id = $data->id;

        if($response->getStatusCode() == 201){
            $resp = array(
                'token' =>  $token,
                'id'    =>  $id
            );
        }else{
            return $response;
        }



        return $this->restV2(200, "ok", "Token created succesfull", $resp);

    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id=null){

        if($request->request->has('cname')) throw new HttpException(400, "Parameter cname can't be changed");

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);

    }

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:POS";
    }

    function getNewEntity()
    {
        return new CashInTokens();
    }

    function getReference(){
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $array_chars = str_split($chars);
        shuffle($array_chars);

        return substr(implode("", $array_chars),0,5);
    }

}