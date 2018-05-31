<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\CreditCard;
use Telepay\FinancialApiBundle\Controller\BaseApiController;

class CreditCardController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:CreditCard";
    }

    function getNewEntity()
    {
        return new CreditCard();
    }

    /**
     * @Rest\View
     */
    public function registerCard(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $group = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        $paramNames = array(
            'owner',
            'card_number',
            'expiration_month',
            'expiration_year',
            'cvc'
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
        $card->setOwner($params['owner']);
        $card->setCardNumber($params['card_number']);
        $card->setExpirationMonth($params['expiration_month']);
        $card->setExpirationYear($params['expiration_year']);
        $card->setCvc($params['cvc']);
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
        $cards = $em->getRepository('TelepayFinancialApiBundle:CreditCard')->findBy(array(
            'company'   =>  $company,
            'user'   =>  $user
        ));
        return $this->restV2(200, 'ok', 'Request successfull', $cards);
    }

    /**
     * @Rest\View
     */
    public function updateCardFromCompany(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $card = $em->getRepository('TelepayFinancialApiBundle:CreditCard')->find($id);

        if($card->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$card) throw new HttpException(404, 'Card not found');
/*
        if($request->request->has('alias')){
            $card->setAlias($request->request->get('alias'));
        }
        $em->persist($card);
        $em->flush();
*/
        return $this->restV2(204, 'ok', 'Card updated successfully');

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $offer = $em->getRepository('TelepayFinancialApiBundle:CreditCard')->findOneBy(array(
            'id'    =>  $id,
            'company' =>  $user->getActiveGroup()
        ));

        if(!$offer) throw new HttpException(404, 'CreditCard not found');

        return parent::deleteAction($id);

    }
}