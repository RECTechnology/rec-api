<?php

namespace Telepay\FinancialApiBundle\Controller\Open;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\User;

class StatusController extends RestApiController {

    /**
     * @Rest\View
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * This function will return an encoded version of system health, according with the following bitmask:
     *   - RELATIONAL_DB = 0x1
     *   - NOT_RELATIONAL_DB = 0x2
     *   - BLOCKCHAIN_NODE = 0x4
     * so, a fully working system will return "system_status": 7, and a fully down "system_status": 0
     */
    public function status(Request $request){

        $status = 0x0;
        $exceptions = [];

        try {
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            $em->getConnection()->connect();
        } catch (\Exception $e){
            $exceptions []= $e->getMessage();
            $status ^= 0x1;
        }

        try {
            /** @var DocumentManager $odm */
            $odm = $this->get('doctrine_mongodb')->getManager();
            $odm->getConnection()->connect();
        } catch (\Exception $e){
            $exceptions []= $e->getMessage();
            $status ^= 0x2;
        }

        try {
            $wallet = $this->get("net.telepay.driver.easybitcoin.rec");
            $wallet->getinfo();
        } catch (\Exception $e){
            $exceptions []= $e->getMessage();
            $status ^= 0x4;
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            ["system_status" => $status, "exceptions" => $exceptions]
        );
    }
}