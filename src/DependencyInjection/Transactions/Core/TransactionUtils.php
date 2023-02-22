<?php

namespace App\DependencyInjection\Transactions\Core;

use App\Document\Transaction;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class TransactionUtils
{
    /** @var ContainerInterface $container */
    private $container;

    private $doctrine;

    private $mongo;

    public function __construct( $container, $doctrine, $mongo)
    {
        $this->container = $container;
        $this->doctrine = $doctrine;
        $this->mongo = $mongo;
    }

    public function makeTransactionsInternal(Request $request, $content){
        $internal_in = $request->request->get('internal_in', false);
        $internal_out = $request->request->get('internal_out', false);
        $dm = $this->getDocumentManager();
        //make transactions internal if needed
        if($internal_out){
            /** @var Transaction $outTx */
            $outTx = $dm->getRepository('FinancialApiBundle:Transaction')->find($content['id']);
            $outTx->setInternal(true);
        }

        if($internal_in){
            /** @var Transaction $inTx */
            $inTx = $dm->getRepository('FinancialApiBundle:Transaction')->getOriginalTxFromTxId($content['pay_out_info']['txid'], Transaction::$TYPE_IN);
            $inTx->setInternal(true);
        }

        $dm->flush();
    }

    /**
     * @return DocumentManager
     */
    private function getDocumentManager(): DocumentManager
    {
        /** @var DocumentManager $dm */
        $dm = $this->mongo->getManager();
        return $dm;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        return $em;
    }

}