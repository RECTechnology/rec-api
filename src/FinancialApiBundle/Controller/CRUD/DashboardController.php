<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Repository\AppRepository;
use App\FinancialApiBundle\Repository\TransactionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DashboardController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class DashboardController extends CRUDController {

    /*
     * - total private accounts
     * - total company accounts
     * - total balances rec
     * - total rec transactions
     * - volume 24h
     * - timeserie registers year, 30d, 7d, 1d (private and company)
     * - timeserie transactions year, 30d, 7d, 1d (count and volume)
     * - per neighbourhood account count
     */

    /**
     * @param $subject
     * @return Response
     */
    public function totalAction($subject){

        switch ($subject){
            case 'private':
            case 'company':
                /** @var EntityManagerInterface $em */
                $em = $this->getDoctrine()->getManager();
                /** @var AppRepository $repo */
                $repo = $em->getRepository(Group::class);
                return $this->restV2(
                    Response::HTTP_OK,
                    "Private accounts total got successfully",
                    ["total" => intval($repo->count(['type' => $subject]))]
                );
            case 'transactions':
                /** @var DocumentManager $em */
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                /** @var TransactionRepository $repo */
                $repo = $dm->getRepository(Transaction::class);
                return $this->restV2(
                    Response::HTTP_OK,
                    "Private accounts total got successfully",
                    ["total" => intval($repo->count())]
                );
        }
        throw new HttpException(Response::HTTP_BAD_REQUEST, "Bad request: invalid total subject");
    }

}
