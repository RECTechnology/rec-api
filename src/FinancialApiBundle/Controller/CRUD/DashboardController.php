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
use Doctrine\ORM\Query\Expr\Join;
use DoctrineExtensions\Query\Sqlite\Day;
use DoctrineExtensions\Query\Sqlite\Month;
use DoctrineExtensions\Query\Sqlite\Year;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function foo\func;

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
    public function totalODMAction()
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
                    "success",
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
                    "success",
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
            ->select('n.id, n.name, n.description, count(a) as accounts_total')
            ->innerJoin(Group::class, 'a', Join::WITH, 'a.neighbourhood = n.id')
            ->groupBy('n')
            ->getQuery()
            ->getResult();

        $result = $this->securizeOutput($result);
        return $this->restV2(
            Response::HTTP_OK,
            "ok",
            "Total obtained successfully",
            $result
        );
    }

    const GROUPING_FUNCTIONS = [
        'year' => [
            'since' => '-1 years',
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-00 00:00:00'",
        ],
        'month' => [
            'since' => '-1 months',
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-', DAY(u.created), ' 00:00:00'"

        ],
        'day' => [
            'since' => '-1 days',
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-', DAY(u.created), ' ', HOUR(u.created), ':00:00'"
        ],
    ];

    /**
     * @param $interval
     * @return Response
     * @throws \Exception
     */
    function timeSeriesRegisters($interval){
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var AppRepository $repo */
        $repo = $em->getRepository(Group::class);

        $privateSerie = $this->getTimeSeriesForAccountType($repo, 'PRIVATE', $interval);
        $companiesSerie = $this->getTimeSeriesForAccountType($repo, 'COMPANY', $interval);
        $result = $privateSerie;
        foreach ($companiesSerie as $cItem){
            $found = false;
            foreach ($result as &$rItem){
                if($rItem['time'] == $cItem['time']){
                    $rItem['company'] = $cItem['company'];
                    $found = true;
                    break;
                }
            }
            if(!$found) $result []= $cItem;
        }

        $result = array_map(function ($el){
            $el['private'] = isset($el['private'])?intval($el['private']):0;
            $el['company'] = isset($el['company'])?intval($el['company']):0;
            return $el;
        }, $result);

        return $this->restV2(
            Response::HTTP_OK,
            "ok",
            "Total obtained successfully",
            $result
        );
    }

    /**
     * @param AppRepository $repo
     * @param $type
     * @param $interval
     * @return mixed
     * @throws \Exception
     */
    private function getTimeSeriesForAccountType($repo, $type, $interval){
        $dateExpr = static::GROUPING_FUNCTIONS[$interval]['date_expr'];
        $select = "CONCAT($dateExpr) as time, count(a) as " . strtolower($type);
        $since = new \DateTime(static::GROUPING_FUNCTIONS[$interval]['since']);
        $since->setTimezone(new \DateTimeZone("UTC"));
        $query = $repo->createQueryBuilder('a')
            ->select($select)
            ->innerJoin(User::class, 'u', Join::WITH, 'a.kyc_manager = u.id')
            ->where('u.created > :oneIntervalAgo')
            ->setParameter('oneIntervalAgo', $since->format('c'))
            ->groupBy('time')
            ->andWhere("a.type = '$type'")
            ->getQuery();
        return $query->getResult();
    }
}
