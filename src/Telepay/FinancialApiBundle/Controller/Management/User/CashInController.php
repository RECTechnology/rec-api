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

        foreach($all as $one){
            $service_cname = $one->getService();
            $service = $this->get('net.telepay.services.'.$service_cname.'.v1');
            $info = $service->getInfo();
            if($service_cname == 'easypay'){
                $one->setAccountNumber($info['account_number']);
            }elseif($service_cname == 'sepa_in'){
                $one->setAccountNumber($info['iban']);
                $one->setBeneficiary($info['beneficiary']);
                $one->setBicSwift($info['bic_swift']);
            }

        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all
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
            $resp = $service->getInfo();
            $resp['token'] =  $token;
            $resp['id']    =  $id;

        }else{
            return $response;
        }

        return $this->restV2(201, "ok", "Token created succesfull", $resp);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $actual_token = $em->getRepository('TelepayFinancialApiBundle:CashInTokens')->findOneBy(array(
            'user'  =>  $user,
            'id'   =>  $id
        ));

        if(!$actual_token) throw new HttpException(405, 'Not allowed');

        $new_token = $this->getReference();
        $request->request->add(array(
            'token' =>  $new_token
        ));

        $response = parent::updateAction($request, $id);

        if($response->getStatusCode() == 204){
            $resp = array(
                'token' =>  $actual_token->getToken(),
                'id'    =>  $id
            );
        }else{
            return $response;
        }

        return $this->restV2(200, "ok", "Token updated succesfull", $resp);


    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);

    }

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:CashInTokens";
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