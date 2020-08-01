<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\CreditCard;
use App\FinancialApiBundle\Controller\BaseApiController;

class CreditCardController extends BaseApiController{

    function getRepositoryName()
    {
        return "FinancialApiBundle:CreditCard";
    }

    function getNewEntity()
    {
        return new CreditCard();
    }

    /**
     * @Rest\View
     */
    public function registerCard(Request $request){
        throw new HttpException(404, 'Method not allowed');

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $group = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();

        $paramNames = array(
            'alias'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }
        $em = $this->getDoctrine()->getManager();
        $card = new CreditCard();
        $card->setCompany($group);
        $card->setUser($user);
        $em->persist($card);
        $em->flush();
        return $this->restV2(201,"ok", "Card registered successfully", $card);
    }


    /**
     * @Rest\View
     */
    public function indexCards(Request $request){
        $user = $this->getUser();
        $company = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $cards = $em->getRepository(CreditCard::class)->findBy(
            ['company'   =>  $company, 'deleted'=>false, 'user' => $user]
        );

        $resp = $this->secureOutput($cards);
        return $this->restV2(200, 'ok', 'Request successfull', $resp);
    }

    /**
     * @Rest\View
     */
    public function updateCardFromCompany(Request $request, $id){
        throw new HttpException(404, 'Method not allowed');

        $em = $this->getDoctrine()->getManager();
        $card = $em->getRepository('FinancialApiBundle:CreditCard')->find($id);
        if($card->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');
        if(!$card) throw new HttpException(404, 'Card not found');
        return $this->restV2(204, 'ok', 'Card updated successfully');
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $credit_card = $em->getRepository('FinancialApiBundle:CreditCard')->findOneBy(array(
            'id'    =>  $id,
            'deleted'=>false,
            'company' =>  $user->getActiveGroup()
        ));
        if(!$credit_card) throw new HttpException(404, 'CreditCard not found');
        $credit_card->setDeleted(true);
        $em->persist($credit_card);
        $em->flush();
        return $this->restV2(204,"ok", "Card deleted successfully");
    }
}