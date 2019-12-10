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
use function Doctrine\ORM\QueryBuilder;
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

        $qb = $repo->createQueryBuilder('n');
        $result = $qb
            ->select('n.id, n.name, n.description, count(a) as accounts_total')
            ->where("a.type = 'COMPANY'")
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

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var AppRepository $repo */
        $repo = $em->getRepository(Group::class);
        $offset = static::GROUPING_FUNCTIONS[$interval]['interval_offset'];

        $privateSerie = $this->getTimeSeriesForAccountType($repo, 'PRIVATE', $interval);
        $companiesSerie = $this->getTimeSeriesForAccountType($repo, 'COMPANY', $interval);
        $result = [];
        foreach ($privateSerie as $item) {
            $result[$item['time']] = ['time' => $item['time'], 'private' => $item['total']];
        }

        foreach ($companiesSerie as $item) {
            if(!array_key_exists($item['time'], $result)){
                $result[$item['time']] = ['time' => $item['time'], 'company' => $item['total']];
            }
            else {
                $result[$item['time']]['company'] = $item['total'];
            }
        }

        $result = array_values($result);
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
            'interval' => 'MONTH',
            'interval_format' => 'n',
            'interval_offset' => 1,
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-01T00:00:00+00:00'",
        ],
        'month' => [
            'since' => "-1 month +1 day 00:00",
            'interval' => 'DAY',
            'interval_format' => 'j',
            'interval_offset' => 1,
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-', DAY(u.created), 'T00:00:00+00:00'"

        ],
        'day' => [
            'since' => "-1 day",
            'interval' => 'HOUR',
            'interval_format' => 'G',
            'interval_offset' => 0,
            'date_expr' => "YEAR(u.created), '-', MONTH(u.created), '-', DAY(u.created), 'T', HOUR(u.created), ':00:00+00:00'"
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
        $select = "CONCAT($dateExpr) as time, count(a) as total";
        $since = new \DateTime(static::GROUPING_FUNCTIONS[$intervalName]['since']);
        $now = new \DateTime();
        if($intervalName == 'day') {
            $since->setTime($now->format('H'),0,0,0);
            $since->modify("+1 hour");
        }
        $since->setTimezone(new \DateTimeZone("UTC"));
        $query = $repo->createQueryBuilder('a')
            ->select($select)
            ->innerJoin(User::class, 'u', Join::WITH, 'a.kyc_manager = u.id')
            ->where('u.created > :oneIntervalAgo')
            ->setParameter('oneIntervalAgo', $since->format('c'))
            ->groupBy('time')
            ->andWhere("a.type = '$type'")
            ->getQuery();
        $qResult = $query->getResult();

        $interval = "+1" . static::GROUPING_FUNCTIONS[$intervalName]['interval'];
        $result = [];
        /** @var \DateTime $time */
        for($time = $since; $time < $now; $time->modify($interval)){
            $item = ['time' => $time->format('c')];
            $item['total'] = 0;
            foreach ($qResult as $qItem){
                if($qItem['time'] == $item['time']){
                    $item['total'] = intval($qItem['total']);
                    break;
                }
            }
            $result []= $item;
        }

        return $result;
    }

    const MONGO_ITEMS = ['month' => 1, 'day' => 1, 'hour' => 0];
    function getIntervalStart($mongoResult){
        $date = new \DateTime("{$mongoResult['year']}-01-01");
        foreach (self::MONGO_ITEMS as $item => $offset){
            if(array_key_exists($item, $mongoResult)){
                $sum = $mongoResult[$item] - $offset;
                $date->modify("+$sum $item");
            }
            else return $date;
        }
        return $date;
    }

    /**
     * @param $intervalName
     * @return Response
     * @throws \Exception
     */
    function timeSeriesTransactions($intervalName){

        /** @var DocumentManager $em */
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        /** @var TransactionRepository $repo */
        $repo = $dm->getRepository(Transaction::class);

        $now = new \DateTime();
        $since = new \DateTime(static::GROUPING_FUNCTIONS[$intervalName]['since']);
        if($intervalName == 'day') {
            $since->setTime($now->format('H'),0,0,0);
            $since->modify("+1 hour");
        }
        $qResult = $repo->statistics($since, $now, $intervalName);

        $interval = "+1" . static::GROUPING_FUNCTIONS[$intervalName]['interval'];
        $result = [];
        /** @var \DateTime $time */
        for($time = $since; $time < $now; $time->modify($interval)){
            $item = ['time' => $time->format('c')];
            $item['count'] = 0;
            $item['volume'] = 0;
            foreach ($qResult as $qItem){
                if($this->getIntervalStart($qItem['_id']) == $time){
                    $item['count'] = intval($qItem['number']);
                    $item['volume'] = intval($qItem['volume']);
                    break;
                }
            }
            $result []= $item;
        }

        //$result = $this->shiftResult($result, $intervalName);
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
