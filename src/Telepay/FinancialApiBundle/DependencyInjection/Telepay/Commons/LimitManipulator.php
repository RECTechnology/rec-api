<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Financial\Currency;

class LimitManipulator{
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
            $group_limit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                'tier'  =>  $tier,
                'method'    =>  $method->getCname().'-'.$method->getType()
            ));
        }

        return $group_limit;

    }

    public function checkLimits(Group $group, $method, $amount){

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
            $group_limit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                'tier'  =>  $tier,
                'method'    =>  $method->getCname().'-'.$method->getType()
            ));
        }

        //ya tengo el grup limit
        //TODO get sum last 30 days transactions
        if($group_limit->getSingle() < $amount && $group_limit->getSingle() != -1) throw new HttpException(403, 'Single Limit Exceeded');
        $total_last_day = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 1);
        if($group_limit->getDay() < ($total_last_day[0]['total'] + $amount) && $group_limit->getDay() != -1) throw new HttpException(403, 'Day Limit Exceeded. '.($total_last_day[0]['total'] + $amount).'-'.$group_limit->getDay());
        $total_last_week = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 7);
        if($group_limit->getWeek() < ($total_last_week[0]['total'] + $amount) && $group_limit->getWeek() != -1) throw new HttpException(403, 'Week Limit Exceeded'.($total_last_week[0]['total'] + $amount).'-'.$group_limit->getWeek());
        $total_last_month = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 30);
        if($group_limit->getMonth() < ($total_last_month[0]['total'] + $amount) && $group_limit->getMonth() != -1) throw new HttpException(403, 'Month Limit Exceeded '.($total_last_month[0]['total'] + $amount).'-'.$group_limit->getMonth());
        $total_last_year = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 360);
        if($group_limit->getYear() < ($total_last_year[0]['total'] + $amount) && $group_limit->getYear() != -1) throw new HttpException(403, 'Year Limit Exceeded'.($total_last_year[0]['total'] + $amount).'-'.$group_limit->getYear());

    }

    public function checkExchangeLimits(Group $group, $amount, $from, $to){

        $this->trans_logger->info('LIMIT_MANIPULATOR (checkExchangeLimits)=> amount='.$amount.' from'.$from.' to='.$to);

        //check if has specific limit
        $em = $this->doctrine->getManager();

        $limit = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findOneBy(array(
            'cname'     =>  'exchange_'.$from.'to'.$to,
            'group'     => $group->getId()
        ));

        if(!$limit){
            $limit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                'method'    =>  'exchange_'.$from,
                'tier'  =>  $group->getTier()
            ));
            $this->trans_logger->info('LIMIT_MANIPULATOR day exchange_'.$from);
            $this->trans_logger->info('LIMIT_MANIPULATOR day'.$limit->getDay());
            //Se añade al contador del tier porque el especifico no existe
            $limitCount = (new LimitAdder())->add( $this->_getLimitCount($group, 'exchange_'.$from), $amount);

        }else{
            //este añade el amount al contador especifico
            $limitCount = (new LimitAdder())->add( $this->_getLimitCount($group, 'exchange_'.$from.'to'.$to), $amount);

        }

//        if($limit->getEnabled()==0)throw new HttpException(403, 'Exchange temporally unavailable');

        $checker = new LimitChecker();

        if(!$checker->leq($limitCount, $limit)) throw new HttpException(405,'Limit exceeded');

        $em->flush();

    }

    //deprecated -> not used more
    public function _getLimitCount(Group $group, $cname){
        $em = $this->doctrine->getManager();

        $limitCount = $em->getRepository('TelepayFinancialApiBundle:LimitCount')->findOneBy(array(
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