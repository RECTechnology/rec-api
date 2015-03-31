<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;

class FeeDeal{
    private $doctrine;

    public function __construct($doctrine){
        $this->doctrine=$doctrine;
    }

    /**
     * Group creator
     * Transaction amount
     * Service name
     * Transaction currency
     * Creator fee
     */

    public function deal(User $creator,$amount,$service_cname,$currency,$fee){

        if(!$creator->hasRole('ROLE_SUPER_ADMIN')){
            //obtenemos el grupo
            $group=$creator->getGroups()[0];

            //obtener comissiones del grupo
            $commissions=$group->getCommissions();
            $group_commission=false;
            foreach ( $commissions as $commission ){
                if ( $commission->getServiceName() == $service_cname ){
                    $group_commission = $commission;
                }
            }

            $fixed=$group_commission->getFixed();
            $variable=$group_commission->getVariable()*$amount;
            $total=$fixed+$variable;
        }else{
            $total=0;
        }

        $em = $this->doctrine->getManager();

        //Ahora lo añadimos al wallet correspondiente
        $wallets=$creator->getWallets();
        foreach($wallets as $wallet){
            if($wallet->getCurrency() === $currency){

                //Añadimos la pasta al wallet
                $wallet->setAvailable($wallet->getAvailable()+$fee-$total);
                $wallet->setBalance($wallet->getBalance()+$fee-$total);
                $em->persist($wallet);
                $em->flush();
            }
        }

        if(!$creator->hasRole('ROLE_SUPER_ADMIN')){
            $new_creator=$group->getCreator();
            $this->deal($new_creator,$amount,$service_cname,$currency,$total);
        }

        return true;

    }
}