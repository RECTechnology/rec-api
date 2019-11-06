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
use Doctrine\ODM\MongoDB\MongoDBException;
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
     * @throws MongoDBException
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

                $query = $repo->createQueryBuilder('a')
                    ->select('sum(w.available) as total')
                    ->leftJoin(UserWallet::class, 'w', Join::WITH, 'w.group = a.id')
                    ->getQuery();
                $result = $query->getSingleResult();

                return $this->restV2(
                    Response::HTTP_OK,
                    "success",
                    "Total obtained successfully",
                    ['total' => intval($result['total'])]
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


    /**
     * @param $interval
     * @return Response
     * @throws \Exception
     */
    function timeSeriesRegisters($interval){
        $xLabels = [
            'year' => ['Jan', 'Feb', 'Mar', 'May', 'Apr', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dec'],
            'month' => range(1, 31),
            'day' => range(0, 23)
        ];

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var AppRepository $repo */
        $repo = $em->getRepository(Group::class);
        $offset = static::GROUPING_FUNCTIONS[$interval]['interval_offset'];

        $privateSerie = $this->getTimeSeriesForAccountType($repo, 'PRIVATE', $interval);
        $companiesSerie = $this->getTimeSeriesForAccountType($repo, 'COMPANY', $interval);
        $result = [];
        foreach ($xLabels[$interval] as $index => $label){
            $item = ['label' => $label, 'private' => 0, 'company' => 0];
            foreach ($privateSerie as $serieItem){
                if($serieItem['interval'] == ($index + $offset)){
                    $item['private'] = intval($serieItem['total']);
                    break;
                }
            }
            foreach ($companiesSerie as $serieItem){
                if($serieItem['interval'] == ($index + $offset)){
                    $item['company'] = intval($serieItem['total']);
                    break;
                }
            }
            $result []= $item;
        }

        $result = $this->shiftResult($result, $interval);
        return $this->restV2(
            Response::HTTP_OK,
            "ok",
            "Total obtained successfully",
            $result
        );
    }

    const GROUPING_FUNCTIONS = [
        'year' => [
            'since' => "first day of next month -1 year 00:00",
            'interval_func' => 'MONTH',
            'interval_format' => 'n',
            'interval_offset' => 1,
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created)",
        ],
        'month' => [
            'since' => "-1 month +1 day 00:00",
            'interval_func' => 'DAY',
            'interval_format' => 'j',
            'interval_offset' => 1,
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-', DAY(u.created)"

        ],
        'day' => [
            'since' => "-23 hours",
            'interval_func' => 'HOUR',
            'interval_format' => 'G',
            'interval_offset' => 0,
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-', DAY(u.created), ' ', HOUR(u.created)"
        ],
    ];

    /**
     * @param AppRepository $repo
     * @param $type
     * @param $intervalName
     * @return mixed
     * @throws \Exception
     */
    private function getTimeSeriesForAccountType($repo, $type, $intervalName){
        $dateExpr = static::GROUPING_FUNCTIONS[$intervalName]['date_expr'];
        $intervalFunc = static::GROUPING_FUNCTIONS[$intervalName]['interval_func'];
        $select = "CONCAT($dateExpr) as time, $intervalFunc(u.created) as interval, count(a) as total";
        $since = new \DateTime(static::GROUPING_FUNCTIONS[$intervalName]['since']);
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


    /**
     * @param $interval
     * @return Response
     * @throws \Exception
     */
    function timeSeriesTransactions($interval){

        $xLabels = [
            'year' => ['Jan', 'Feb', 'Mar', 'May', 'Apr', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dec'],
            'month' => range(1, 31),
            'day' => range(0, 23)
        ];

        /** @var DocumentManager $em */
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        /** @var TransactionRepository $repo */
        $repo = $dm->getRepository(Transaction::class);

        $now = new \DateTime();
        $since = new \DateTime(static::GROUPING_FUNCTIONS[$interval]['since']);
        $dbResult = $repo->statistics($since, $now, $interval);

        $result = [];
        $offset = static::GROUPING_FUNCTIONS[$interval]['interval_offset'];
        foreach ($xLabels[$interval] as $index => $label){
            $item = ['label' => $label, 'count' => 0, 'volume' => 0];
            foreach ($dbResult as $dbItem){
                if($dbItem['_id'][strtolower(static::GROUPING_FUNCTIONS[$interval]['interval_func'])] == ($index + $offset)){
                    $item['count'] = $dbItem['number'];
                    $item['volume'] = $dbItem['volume'];
                    break;
                }
            }
            $result []= $item;
        }
        $result = $this->shiftResult($result, $interval);
        return $this->restV2(
            Response::HTTP_OK,
            "ok",
            "Total obtained successfully",
            $result
        );
    }

    private function shiftResult($result, $interval){
        $format = static::GROUPING_FUNCTIONS[$interval]['interval_format'];
        $middle = intval((new \DateTime())->format($format));
        $secondPart = array_slice($result, 0, $middle);
        $firstPart = array_slice($result, $middle);
        return array_merge($firstPart, $secondPart);
    }
}
