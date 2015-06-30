<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use FOS\OAuthServerBundle\Document\RefreshToken;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Telepay\FinancialApiBundle\Entity\AccessToken;
use Telepay\FinancialApiBundle\Entity\BTCWallet;
use Telepay\FinancialApiBundle\Entity\Device;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\POS;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class POSController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function indexAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $all = $this->getRepository()->findBy(array(
            'user'  =>  $user
        ));

        $total = count($all);

        $filtered = [];
        foreach($all as $tpv){
            $filtered[] = $tpv->getTpvView();
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $filtered
            )
        );
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $request->request->add(array(
            'user'   =>  $user
        ));

        //TODO comprobar que el servicio existe.
        $currency = $request->request->get('currency');
        $request->request->remove('currency');
        $request->request->add(array('currency'=> strtoupper($currency)));

        return parent::createAction($request);

    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id=null){

        if($request->request->has('cname')) throw new HttpException(400, "Parameter cname can't be changed");

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);

    }

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:POS";
    }

    function getNewEntity()
    {
        return new POS();
    }


}