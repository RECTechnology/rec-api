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

class CreditCardController extends RestApiController{

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
        if($request->request->has('iban')) {
            $card->setIban($request->request->get('iban'));
        }
        $em->persist($card);
        return $this->restV2(201,"ok", "Card registered successfully", $card);
    }



    /**
     * @Rest\View
     */
    public function indexCards(Request $request){
        $user = $this->getUser();
        $company = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $cards = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findBy(array(
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
        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->find($id);

        if($card->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$card) throw new HttpException(404, 'Card not found');

        if($request->request->has('alias')){
            $card->setAlias($request->request->get('alias'));
        }
        $em->flush();
        return $this->restV2(204, 'ok', 'Card updated successfully');

    }
}