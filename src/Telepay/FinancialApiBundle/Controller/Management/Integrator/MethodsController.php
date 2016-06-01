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

        $user = $this->get('security.context')->getToken()->getUser();

        $methods = $user->getMethodsList();

        if(!in_array($method, $methods)) throw new HttpException(404, 'Method not allowed');

        $methods = $this->get('net.telepay.method_provider')->findByCname($method);

        $response = array(
            'cname' =>  $methods->getCname(),
            'type' =>  $methods->getType(),
            'currency'  =>  $methods->getCurrency(),
            'scale' =>  Currency::$SCALE[$methods->getCurrency()],
            'base64image'   =>  $methods->getBase64Image()
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

        $user = $this->get('security.context')->getToken()->getUser();
        $group = $user->getGroups()[0];
        $methods = $group->getMethodsList();

        $response = array();

        if(count($methods) == 0) throw new HttpException (404, 'No methods found for this company');

        foreach($methods as $method){
            $methodsEntity = $this->get('net.telepay.method_provider')->findByCname($method);

            if($methodsEntity){
                $resp = array(
                    'cname' =>  $methodsEntity->getCname(),
                    'type' =>  $methodsEntity->getType(),
                    'currency'  =>  $methodsEntity->getCurrency(),
                    'scale' =>  Currency::$SCALE[$methodsEntity->getCurrency()],
                    'base64image'   =>  $methodsEntity->getBase64Image()
                );

                $response[] = $resp;
            }

        }

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $response
        );
    }

}
