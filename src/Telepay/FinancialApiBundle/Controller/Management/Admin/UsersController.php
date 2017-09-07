<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ResellerDealer;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\User;

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
    public function showAction($id){
        $user = $this->get('security.context')->getToken()->getUser();

        $user = $this->getRepository()->find($id);

        if(!$user) throw new HttpException(404,'User not found');

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => 1,
                'start' => 0,
                'end' => 1,
                'elements' => $user->getAdminView()
            )
        );
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id){
       return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function userActionsAction(Request $request,$id, $action){


        $em = $this->getDoctrine()->getmanager();
        $user = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$user) throw new HttpException(404, 'User not found');
        //posible actions: resend_email, resend_sms,
        switch ($action){
            case 'resend_email':
                //Your own logic
                $email = $user->getEmail();
                $request->request->add(array(
                    'email' =>  $email
                ));
                $response = $this->forward('Telepay\FinancialApiBundle\Controller\Management\User\AccountController::sentValidationEmailAction', array('request'=>$request));
                return $response;

                break;
            case 'resend_sms':
                //Your own logic

                $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
                    'user' => $user
                ));
                $phone_json = json_decode($kyc->getPhone());
                $prefix = $phone_json->prefix;
                $phone = $phone_json->number;
                //TODO est no pasa parametros por la request de momento
                $request->request->add(array(
                    'user'  =>  $user,
                    'prefix'    =>  $prefix,
                    'phone' =>  $phone
                ));
                $response = $this->forward('Telepay\FinancialApiBundle\Controller\KycController::validatePhone');
                return $response;
                break;
            default:
                break;
        }

        return $this->restV2(204, 'Success', 'Updated successfully');
    }

}
