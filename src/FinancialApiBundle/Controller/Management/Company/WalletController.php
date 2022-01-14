<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use App\FinancialApiBundle\Entity\Campaign;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Financial\Currency;

/**
 * Class WalletController
 * @package App\FinancialApiBundle\Controller\Management\Company
 */
class WalletController extends RestApiController {

    /**
     * @Rest\View
     * description: add comment to transaction
     */
    public function updateAction(Request $request,$id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($trans->getGroup());

        $user = $this->getUser();
        if(!$user->hasGroup($company->getName())) throw new HttpException(403, 'You don\'t have the necessary permissions in this company');
        //TODO check if is granted role worker
        if($request->request->has('comment') && $request->request->get('comment') != ''){
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
