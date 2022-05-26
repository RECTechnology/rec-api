<?php

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Qualification;
use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\ORM\EntityManagerInterface;

class ShopBadgeHandler
{

    private $doctrine;

    public function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function recalculateShopBadge(Qualification $qualification): void
    {
        $settings = $this->getSettings();

        /** @var Group $shop */
        $shop = $qualification->getAccount();

        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        //search last 10
        $qualifications = $em->getRepository(Qualification::class)->findBy(array(
            'badge' => $qualification->getBadge(),
            'account' => $shop,
            'status' => Qualification::STATUS_REVIEWED
        ),
        ['updated' => 'DESC'],
            $settings['max_qualifications']);

        $total = count($qualifications);
        $positiveReview = 0;
        $threshold = $total * $settings['threshold'];
        if($total >= $settings['min_qualifications']){
            /** @var Qualification $qualy */
            foreach ($qualifications as $qualy){
                if($qualy->getValue() === 1){
                    $positiveReview++;
                }
            }
            if($positiveReview >= $threshold){
                try {
                    $shop->addBadge($qualification->getBadge(), true);
                    $em->flush();
                }catch (PreconditionFailedException $e){
                    //do nothing
                }

            }else{
                try {
                    $shop->delBadge($qualification->getBadge());
                    $em->flush();
                }catch (PreconditionFailedException $e){
                    //do nothing
                }

            }
        }

    }

    private function getEntityManager(){
        return $this->doctrine->getManager();
    }

    private function getSettings(){
        $em = $this->getEntityManager();
        $settings = $em->getRepository(ConfigurationSetting::class)->findBy(array('scope' => ConfigurationSetting::SHOP_BADGES_SCOPE ));

        $shopBadgesSettings = array();

        foreach ($settings as $set){
            $shopBadgesSettings[$set->getName()] = $set->getValue();
        }

        if(!isset($shopBadgesSettings['threshold'])) $shopBadgesSettings['threshold'] = 0.5;
        if(!isset($shopBadgesSettings['min_qualifications'])) $shopBadgesSettings['min_qualifications'] = 0;
        if(!isset($shopBadgesSettings['max_qualifications'])) $shopBadgesSettings['max_qualifications'] = 10;

        return $shopBadgesSettings;
    }

}