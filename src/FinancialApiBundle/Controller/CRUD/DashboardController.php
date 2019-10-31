<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Balance;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Neighbourhood;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Repository\AppRepository;
use App\FinancialApiBundle\Repository\TransactionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineExtensions\Query\Sqlite\Day;
use DoctrineExtensions\Query\Sqlite\Month;
use DoctrineExtensions\Query\Sqlite\Year;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DashboardController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class DashboardController extends CRUDController {

    /*
     * - total private accounts [OK]
     * - total company accounts [OK]
     * - total balances rec [OK]
     * - total rec transactions [OK]
     * - timeserie registers year, 30d, 7d, 1d (private and company)
     * - timeserie transactions year, 30d, 7d, 1d (count and volume)
     * - per neighbourhood account count [OK]
     */


    /**
     * @return Response
     */
    public function totalODMActionAction()
    {
        /** @var DocumentManager $em */
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        /** @var TransactionRepository $repo */
        $repo = $dm->getRepository(Transaction::class);
        return $this->restV2(
            Response::HTTP_OK,
            "ok",
            "Private accounts total got successfully",
            ["total" => intval($repo->count())]
        );
    }


    /**
     * @param $subject
     * @return Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function totalORMAction($subject){
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        switch ($subject){
            case 'private':
            case 'company':
                /** @var AppRepository $repo */
                $repo = $em->getRepository(Group::class);
                return $this->restV2(
                    Response::HTTP_OK,
                    "Total obtained successfully",
                    ["total" => intval($repo->count(['type' => $subject]))]
                );
            case 'balance':
                /** @var AppRepository $repo */
                $repo = $em->getRepository(Group::class);

                $result = $repo->createQueryBuilder('a')
                    ->select('sum(w.available)')
                    ->join(UserWallet::class, 'w')
                    ->getQuery()
                    ->getSingleResult();

                return $this->restV2(
                    Response::HTTP_OK,
                    "ok",
                    "Total obtained successfully",
                    ["total" => intval($result)]
                );
        }
        throw new HttpException(Response::HTTP_BAD_REQUEST, "Bad request: invalid total subject");
    }

    /**
     * @return Response
     */
    public function neighbourhoodTotalsAction(){
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var AppRepository $repo */
        $repo = $em->getRepository(Neighbourhood::class);

        $result = $repo->createQueryBuilder('n')
            ->select('n, count(a) as accounts_total')
            ->join(Group::class, 'a')
            ->groupBy('n')
            ->getQuery()
            ->getResult();

        $result = $this->securizeOutput($result);
        return $this->restV2(
            Response::HTTP_OK,
            "ok",
            "Total obtained successfully",
            ["total" => $result]
        );
    }

    const INTERVAL_GROUPING_FUNCTIONS = [
        'year' => ['seconds' => 3600 * 24 * 365, 'grouping' => 'MONTH'],
        'month' => ['seconds' => 3600 * 24, 'grouping' => 'DAY'],
        'day' => ['seconds' => 3600, 'grouping' => 'HOUR'],
    ];

    /**
     * @param $interval
     * @return Response
     */
    function timeSeriesRegisters($interval){
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var AppRepository $repo */
        $repo = $em->getRepository(Group::class);

        $qb = $repo->createQueryBuilder('a');
        $select = "count(a) as count, {$this::INTERVAL_GROUPING_FUNCTIONS[$interval]['grouping']}(u.created) as interval";
        $result = $qb->select($select)
            ->join(User::class, 'u')
            ->where($qb->expr()->gt('u.created', ':oneYearAgo'))
            ->setParameter('oneYearAgo', time() - $this::INTERVAL_GROUPING_FUNCTIONS[$interval]['seconds'])
            ->groupBy('interval')
            ->getQuery()
            ->getResult();
        return $this->restV2(
            Response::HTTP_OK,
            "ok",
            "Total obtained successfully",
            ["months" => $result]
        );
    }
}
