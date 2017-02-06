<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class WalletController
 * @package Telepay\FinancialApiBundle\Controller\Management\Company
 */
class WalletController extends RestApiController {

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($trans->getGroup());

        $user = $this->getUser();
        if(!$user->hasGroup($company->getName())) throw new HttpException(403, 'You don\'t have the necessary permissions in this company');
        if($request->request->has('comment') && $request->request->get('comment')){
            $comment = $trans->getComment();
            $comment[] = $request->request->get('comment');
            $trans->setComment($comment);

        }else{
            throw new HttpException(404, 'No valid params found');
        }

        $dm->flush();

        return $this->restV2(204,"ok", "Updated");
    }


}
