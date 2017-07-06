<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Integrator;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class MethodsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Integrator
 */
class MethodsController extends RestApiController {

    /**
     * @Rest\View()
     */
    public function read($method) {

        //check if the user has the method

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        $methods = $userGroup->getMethodsList();

        if(!in_array($method, $methods)) throw new HttpException(404, 'Method not allowed');

        $methods = $this->get('net.telepay.method_provider')->findByCname($method);

        $response = array(
            'cname' =>  $methods->getCname(),
            'type' =>  $methods->getType(),
            'currency'  =>  $methods->getCurrency(),
            'scale' =>  Currency::$SCALE[$methods->getCurrency()],
            'base64image'   =>  $methods->getBase64Image(),
            'image'   =>  $methods->getImage()
        );

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $response
        );
    }

    /**
     * @Rest\View()
     */
    public function index() {

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        $tier = $userGroup->getTier();
        if($userGroup->getGroupCreator()->getId() == $this->container->getParameter('default_company_creator_commerce_android_fair')){
            $tier = 'fairpay';
        }
        $methodsByTier = $this->get('net.telepay.method_provider')->findByTier($tier);

        //TODO check status
        //check if method is available

        $em = $this->getDoctrine()->getManager();

        foreach ($methodsByTier as $method){

            $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
                'method'    =>  $method->getCname(),
                'type'      =>  $method->getType()
            ));

            $method->setStatus($statusMethod->getStatus());
        }

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $methodsByTier
        );
    }

}
