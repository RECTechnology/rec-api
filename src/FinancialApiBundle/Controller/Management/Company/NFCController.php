<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\LimitDefinition;
use App\FinancialApiBundle\Entity\LimitCount;
use App\FinancialApiBundle\Entity\NFCCard;
use App\FinancialApiBundle\Entity\ServiceFee;
use App\FinancialApiBundle\Entity\TierValidations;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

class NFCController extends RestApiController{

    /**
     * @Rest\View
     */
    public function registerCard(Request $request){
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
        $uniq_id = $this->_randomId();
        $em = $this->getDoctrine()->getManager();
        $card = new NFCCard();
        $card->setCompany($group);
        $card->setUser($user);
        $card->setAlias($params['alias']);
        $card->setIdCard($uniq_id);
        $em->persist($card);
        $em->flush();
        return $this->restV2(201,"ok", "Card registered successfully", $card);
    }

    private function _randomId() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $id = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 24; $i++) {
            $n = rand(0, $alphaLength);
            $id[] = $alphabet[$n];
        }
        return implode($id);
    }


    /**
     * @Rest\View
     */
    public function disableCard(Request $request){
        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $card = $em->getRepository('FinancialApiBundle:NFCCard')->findOneBy(array(
            'id'    =>  $id
        ));

        if(!$card) throw new HttpException(404, 'NFCCard not found');
        $card->setEnabled(false);
        $em->persist($card);
        $em->flush();
        $response = array(
            'card'     =>  $card->getAlias()
        );
        return $this->restV2(201,"ok", "Deactivate NFC Card succesfully", $response);
    }

    /**
     * @Rest\View
     */
    public function indexCards(Request $request){

        $user = $this->getUser();
        $company = $user->getActiveGroup();

        if(!$company->getPremium()) throw new HttpException(403, 'You don\'t hve the necessary permissions');

        $em = $this->getDoctrine()->getManager();
        $cards = $em->getRepository('FinancialApiBundle:NFCCard')->findBy(array(
            'company'   =>  $company
        ));

        return $this->restV2(200, 'ok', 'Request successfull', $cards);

    }

    /**
     * @Rest\View
     */
    public function updateCardFromCompany(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $card = $em->getRepository('FinancialApiBundle:NFCCard')->find($id);
        if($card->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$card) throw new HttpException(404, 'Card not found');
        if($request->request->has('enabled')){
            $card->setEnabled($request->request->get('enabled'));
        }
        if($request->request->has('pin')){
            $pin = $request->request->get('pin');
            if(strlen($pin)!=4) throw new HttpException(404, 'PIN must have 4 numbers');
            if((string)(int)$pin == $pin) {
                $card->setPin($pin);
            }
            else{
                throw new HttpException(404, 'PIN must have only numbers');
            }
        }
        if($request->request->has('alias')){
            $card->setAlias($request->request->get('alias'));
        }
        $em->flush();
        return $this->restV2(204, 'ok', 'Card updated successfully');
    }
}