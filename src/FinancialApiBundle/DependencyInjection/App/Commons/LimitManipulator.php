<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\MethodInterface;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LimitCount;
use App\FinancialApiBundle\Financial\Currency;

class LimitManipulator {
    private $doctrine;
    private $doctrine_mongo;
    private $container;
    private $trans_logger;

    public function __construct($doctrine, $container, $doctrine_mongo){
        $this->doctrine = $doctrine;
        $this->doctrine_mongo = $doctrine_mongo;
        $this->container = $container;
        $this->trans_logger = $this->container->get('transaction.logger');
    }


    //deprecated NO USAR
    public function getMethodLimits(Group $group, $method){

        $em = $this->doctrine->getManager();

        $group_limits = $group->getLimits();
        $group_limit = false;
        foreach ( $group_limits as $limit ){
            if( $limit->getCname() == $method->getCname().'-'.$method->getType()){
                $group_limit = $limit;
            }
        }

        //if limit doesn't exist search in tierLimit
        if(!$group_limit){
            $tier = $group->getTier();
            $group_limit = $em->getRepository('FinancialApiBundle:TierLimit')->findOneBy(array(
                'tier'  =>  $tier,
                'method'    =>  $method->getCname().'-'.$method->getType()
            ));
        }

        return $group_limit;

    }

    /**
     * @param Group $group
     * @param MethodInterface $method
     * @param $amount
     */
    public function checkLimits(Group $group, $method, $amount){

        /** @var LoggerInterface $txLogger */
        $txLogger = $this->container->get('transaction.logger');

        $funcInfo = self::class . "::checkLimits({$group->getId()}, {$method->getCname()}, {$amount})";

        $em = $this->doctrine->getManager();
        $dm = $this->doctrine_mongo->getManager();

        $group_limits = $group->getLimits();
        $group_limit = false;
        foreach ( $group_limits as $limit ){
            if( $limit->getCname() == $method->getCname().'-'.$method->getType()){
                $group_limit = $limit;
            }
        }

        //if limit doesn't exist search in tierLimit
        if(!$group_limit){
            $tier = $group->getTier();
            $group_limit = $em->getRepository('FinancialApiBundle:TierLimit')->findOneBy(array(
                'tier'  =>  $tier,
                'method'    =>  $method->getCname().'-'.$method->getType()
            ));
        }

        if($group_limit->getSingle() < $amount && $group_limit->getSingle() >= 0)
            //throw new HttpException(403, 'Single Limit Exceeded '.$amount/$scale.' - '.$group_limit->getSingle()/$scale);
            throw new HttpException(403, 'Single Limit Exceeded.');

        $total_last_day = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 1);
        if($group_limit->getDay() < ($total_last_day[0]['total'] + $amount) && $group_limit->getDay() >= 0)
            //throw new HttpException(403, 'Day Limit Exceeded. '.($total_last_day[0]['total'] + $amount)/$scale.' > '.$group_limit->getDay()/$scale);
            throw new HttpException(403, 'Day Limit Exceeded.');

        $total_last_week = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 7);
        if($group_limit->getWeek() < ($total_last_week[0]['total'] + $amount) && $group_limit->getWeek() >= 0)
            //throw new HttpException(403, 'Week Limit Exceeded. '.($total_last_week[0]['total'] + $amount)/$scale.' > '.$group_limit->getWeek()/$scale);
            throw new HttpException(403, 'Week Limit Exceeded.');

        $total_last_month = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 30);
        if($group_limit->getMonth() < ($total_last_month[0]['total'] + $amount) && $group_limit->getMonth() >= 0) {
            $txLogger->info($funcInfo . "Monthly sum for method {$method->getCname()}: $total_last_month, max: {$group_limit->getMonth()}");
            throw new HttpException(403, 'Month Limit Exceeded.');
        }

        $total_last_year = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 360);
        if($group_limit->getYear() < ($total_last_year[0]['total'] + $amount) && $group_limit->getYear() >= 0)
            //throw new HttpException(403, 'Year Limit Exceeded. '.($total_last_year[0]['total'] + $amount)/$scale.' > '.$group_limit->getYear()/$scale);
            throw new HttpException(403, 'Year Limit Exceeded.');

    }

    public function checkExchangeLimits(Group $group, $amount_from, $amount_to, $from, $to){
        $this->trans_logger->info('LIMIT_MANIPULATOR (checkExchangeLimits)=> amount=' . $amount_from . ' from' . $from . ' to=' . $to);

        //check if has specific limit
        $em = $this->doctrine->getManager();
        $dm = $this->doctrine_mongo->getManager();

        $group_limit = $em->getRepository('FinancialApiBundle:LimitDefinition')->findOneBy(array(
            'cname' => 'exchange_' . $from . 'to' . $to,
            'group' => $group->getId()
        ));

        //TODO change limit adder by checklimits functions
        if (!$group_limit) {
            $group_limit = $em->getRepository('FinancialApiBundle:TierLimit')->findOneBy(array(
                'method' => 'exchange_' . $to,
                'tier' => $group->getTier()
            ));
            $this->trans_logger->info('LIMIT_MANIPULATOR day exchange_' . $from);
            $this->trans_logger->info('LIMIT_MANIPULATOR day' . $group_limit->getDay());
            //Se añade al contador del tier porque el especifico no existe
//            $limitCount = (new LimitAdder())->add( $this->_getLimitCount($group, 'exchange_'.$from), $amount);
        } else {
            //este añade el amount al contador especifico
//            $limitCount = (new LimitAdder())->add( $this->_getLimitCount($group, 'exchange_'.$from.'to'.$to), $amount);

        }

        $scale = pow(10, Currency::$SCALE[$group_limit->getCurrency()]);

        //get sum last days transactions
        if ($group_limit->getSingle() < $amount_to && $group_limit->getSingle() >= 0) {
            //throw new HttpException(403, 'Single Limit exceeded (' . $group_limit->getSingle()/$scale . ' ' . $group_limit->getCurrency() .'). ' . $amount_to/$scale . ' > ' . $group_limit->getSingle()/$scale);
            throw new HttpException(403, 'Single Limit Exceeded.');
        }

        $total_last_day = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $to, 1);
        if ($group_limit->getDay() < ($total_last_day[0]['total'] + $amount_to) && $group_limit->getDay() >= 0) {
            //throw new HttpException(403, 'Day Limit exceeded (' . $group_limit->getDay()/$scale . ' ' . $group_limit->getCurrency() .'). ' . ($total_last_day[0]['total'] + $amount_to)/$scale . ' > ' . $group_limit->getDay()/$scale);
            throw new HttpException(403, 'Day Limit Exceeded.');
        }

        $total_last_week = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $to, 7);
        if ($group_limit->getWeek() < ($total_last_week[0]['total'] + $amount_to) && $group_limit->getWeek() >= 0) {
            //throw new HttpException(403, 'Week Limit exceeded (' . $group_limit->getWeek()/$scale . ' ' . $group_limit->getCurrency() .'). ' . ($total_last_week[0]['total'] + $amount_to)/$scale . ' > ' . $group_limit->getWeek()/$scale);
            throw new HttpException(403, 'Week Limit Exceeded.');
        }

        $total_last_month = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $to, 30);
        if ($group_limit->getMonth() < ($total_last_month[0]['total'] + $amount_to) && $group_limit->getMonth() >= 0) {
            //throw new HttpException(403, 'Month Limit exceeded (' . $group_limit->getMonth()/$scale . ' ' . $group_limit->getCurrency() .'). ' . ($total_last_month[0]['total'] + $amount_to)/$scale . ' > ' . $group_limit->getMonth()/$scale);
            throw new HttpException(403, 'Month Limit Exceeded.');
        }

        $total_last_year = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $to, 360);
        if ($group_limit->getYear() < ($total_last_year[0]['total'] + $amount_to) && $group_limit->getYear() >= 0){
            //throw new HttpException(403, 'Year Limit exceeded (' . $group_limit->getYear()/$scale . ' ' . $group_limit->getCurrency() .'). ' . ($total_last_year[0]['total'] + $amount_to)/$scale . ' > ' . $group_limit->getYear()/$scale);
            throw new HttpException(403, 'Year Limit Exceeded.');
        }
    }

    //deprecated -> not used any more
    public function _getLimitCount(Group $group, $cname){
        $em = $this->doctrine->getManager();

        $limitCount = $em->getRepository('FinancialApiBundle:LimitCount')->findOneBy(array(
            'group' =>  $group->getId(),
            'cname' =>  $cname
        ));

        //if user hasn't limit create it
        if(!$limitCount){
            $limitCount = LimitCount::createFromController($cname, $group);
            $em->persist($limitCount);
            $em->flush();
        }

        return $limitCount;
    }

}