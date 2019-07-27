<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use Swift_Attachment;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use WebSocket\Exception;

/**
 * Class SpecialActionsController
 * @package App\FinancialApiBundle\Controller\Management\Company
 */
class SpecialActionsController extends RestApiController {

    private function _exchange($amount,$curr_in,$curr_out){

        $dm = $this->get('doctrine')->getManager();
        $exchangeRepo = $dm->getRepository('FinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price = $exchange->getPrice();
        $total = round($amount * $price,0);

        return $total;

    }

    public function changeTier(Request $request, $company_id, $tier){
        /*
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_WORKER')) {
            throw $this->createAccessDeniedException();
        }
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
            'id'  =>  $company_id
        ));
        if(!$group) throw new HttpException(404, 'Group not allowed');
        $group->setTier($tier);
        $em->flush();
        return $this->rest(204, 'Company tier updated successfully');
        */
    }
}
