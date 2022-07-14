<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Admin;

use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\DependencyInjection\App\Commons\Web3ApiManager;
use App\FinancialApiBundle\Entity\Group;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class NFTController extends RestApiController{


    /**
     * @Rest\View
     * @param Request $request
     * @param $account_id
     * @return Response
     */
    public function createWallet(Request $request, $account_id){

        $em = $this->getDoctrine()->getManager();
        $account = $em->getRepository(Group::class)->find($account_id);
        if(!$account) throw new HttpException(404, 'Account not found');
        if($account->getNftWallet() != '') throw new HttpException(404, 'Account already has wallet');
        /** @var Web3ApiManager $web3Manager */
        $web3Manager = $this->container->get('net.app.commons.web3.api_manager');
        $response = $web3Manager->createWallet();
        $account->setNftWallet($response["wallet"]["address"]);
        $account->setNftWalletPk($response["wallet"]["private_key"]);
        $em->persist($account);
        $em->flush();

        return $this->restV2(201, 'success', 'Wallet created', $response);
    }

}