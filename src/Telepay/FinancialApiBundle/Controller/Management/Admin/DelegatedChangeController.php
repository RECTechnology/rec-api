<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\DelegatedChange;
class DelegatedChangeController extends RestApiController{



    /**
     * @Rest\View
     */
    public function showAction($id){
        $em = $this->getDoctrine()->getManager();
        $delegated_change = $em->getRepository('TelepayFinancialApiBundle:DelegatedChange')->find($id);
        $n=0;
        $d=Null;
        /*
        foreach ($delegated_change->getData() as $data){
                $d = $data;
                $n++;

        }
        throw new HttpException(403, $n." pass ".$d->getCvv2());//$d->getCreated()->format('Y-m-d H:i:s'));
        */


        return $this->restV2(200,"ok", "Request successful", $delegated_change);
    }




}