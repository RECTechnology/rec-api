<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\DependencyInjection\Commons;

use App\Entity\Group;
use App\Entity\ServiceFee;

class FeeManipulator{
    private $doctrine;

    public function __construct($doctrine){
        $this->doctrine = $doctrine;
    }


    public function getMethodFees(Group $group, $method){
        $em = $this->doctrine->getManager();

        $group_commissions = $group->getCommissions();
        $group_commission = false;

        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $method->getCname().'-'.$method->getType() ){
                $group_commission = $commission;
            }
        }

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($method->getCname().'-'.$method->getType(), $group);
            $group_commission->setCurrency($method->getCurrency());
            $group_commission->setFixed($method->getDefaultFixedFee());
            $group_commission->setVariable($method->getDefaultVariableFee());
            $em->persist($group_commission);
            $em->flush();
        }

        return $group_commission;
    }

}