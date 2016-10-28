<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\CashInTokens;

class NFCController extends RestApiController{

    /**
     * @Rest\View
     */
    public function registerUserCard(Request $request){

        //TODO check client => only android client is allowed
        //TODO check company => anly certain companies can do this
        //TODO create company
        //TODO create user
        //TODO create wallets
        //TODO create exchanges limits and fees
        //TODO create userGroup

    }

    /**
     * @Rest\View
     */
    public function registerCard(Request $request){

        //TODO check client => only android client is allowed
        //TODO check company => anly certain companies can do this
        //TODO create company
        //TODO create user
        //TODO create wallets
        //TODO create exchanges limits and fees
        //TODO create userGroup

    }

    /**
     * @Rest\View
     */
    public function addFundsToCard(Request $request){

    }

    /**
     * @Rest\View
     */
    public function refreshPINCard(Request $request){

    }

    /**
     * @Rest\View
     */
    public function NFCPayment(Request $request){

    }


}