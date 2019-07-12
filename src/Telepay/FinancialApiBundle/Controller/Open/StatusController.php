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

        $status = 0x7; // all online (111)
        $exceptions = [];

        try {
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            if(!$em->getConnection()->isConnected())
                $em->getConnection()->connect();
        } catch (\Exception $e){
            $status ^= 0x1; // change lsb (001)
            $exceptions []= $e->getMessage();
        }

        try {
            /** @var DocumentManager $odm */
            $odm = $this->get('doctrine_mongodb')->getManager();
            if(!$odm->getConnection()->isConnected())
                $odm->getConnection()->connect();
        } catch (\Exception $e){
            $status ^= 0x2; // change middle-bit (010)
            $exceptions []= $e->getMessage();
        }

        try {
            $wallet = $this->get("net.telepay.driver.easybitcoin.rec");
            $info = $wallet->getinfo();
            if(!isset($info['balance']))
                throw new \LogicException("Node info not working");
        } catch (\Exception $e){
            $status ^= 0x4; // change msb (100)
            $exceptions []= $e->getMessage();
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            ["system_status" => $status, "exceptions" => $exceptions]
        );
    }
}