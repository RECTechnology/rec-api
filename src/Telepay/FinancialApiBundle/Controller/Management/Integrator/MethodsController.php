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
    public function read($method, $id = null) {

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
    public function index($id = null) {

        $em = $this->getDoctrine()->getManager();
        if($id == null){
            $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        }else{
            $userGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($id);

        }

        if(!$userGroup) throw new HttpException(404, 'Company not found');

        //check if user has company
        if(!$this->getUser()->hasGroup($userGroup->getName())) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $tier = $userGroup->getTier();
        $methodsByTier = $this->get('net.telepay.method_provider')->findByTier($tier);

        //check if method is available
        foreach ($methodsByTier as $method){

            $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
                'method'    =>  $method->getCname(),
                'type'      =>  $method->getType()
            ));

            if(!$statusMethod){
                $method->setStatus('disabled');
            }else{
                $method->setStatus($statusMethod->getStatus());
            }

            //add fees to methods
            $fees = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findOneBy(array(
                'group' =>  $userGroup,
                'service_name'  =>  $method->getCName()
            ));

            if($fees){
                $method->setFees($fees);
            }

            //TODO add limits to methods
            $limits = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findOneBy(array(
                'group'=>   $userGroup,
                'cname' =>  $method->getCName().'-'.$method->getType()
            ));

            if($limits){
                $method->setLimits($limits);
            }else{
                //if has no limits defined use tier limits
                $tierLimit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                    'method'    =>  $method->getCName().'-'.$method->getType(),
                    'tier'  =>  $userGroup->getTier()
                ));
                $method->setLimits($tierLimit);
            }


        }

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $methodsByTier
        );
    }

}
