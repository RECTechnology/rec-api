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

    public function __construct($doctrine){
        $this->doctrine = $doctrine;
    }


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

    public function checkExchangeLimits(Group $group, $amount, $from, $to){

        //check if has specific limit
        $em = $this->doctrine->getManager();

        $limit = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findOneBy(array(
            'cname'     =>  'exchange_'.$from.'to'.$to,
            'group'     => $group->getId()
        ));

        if(!$limit){
            $limit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                'method'    =>  'exchange_'.$from
            ));
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