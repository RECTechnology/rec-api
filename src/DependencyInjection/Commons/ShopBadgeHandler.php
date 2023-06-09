<?php

namespace App\DependencyInjection\Commons;

use App\Entity\ConfigurationSetting;
use App\Entity\Group;
use App\Entity\Qualification;
use App\Exception\PreconditionFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

class ShopBadgeHandler
{

    private $doctrine;

    /** @var Logger $logger */
    private $logger;

    public function __construct($doctrine, Logger $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    public function recalculateShopBadge(Qualification $qualification): void
    {
        $this->logger->info('Recalculate badge '.$qualification->getBadge()->getName().' for account '.$qualification->getAccount()->getName());
        $settings = $this->getSettings();

        /** @var Group $shop */
        $shop = $qualification->getAccount();

        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();

        //search last max_qualifications
        $qualifications = $em->getRepository(Qualification::class)->findBy(array(
            'badge' => $qualification->getBadge(),
            'account' => $shop,
            'status' => Qualification::STATUS_REVIEWED
        ),
        ['updated' => 'DESC'],
            $settings['max_qualifications']);

        if($qualifications){
            $total = count($qualifications);
            $positiveReview = 0;
            $threshold = $total * $settings['threshold'];
            if($total >= $settings['min_qualifications']){
                /** @var Qualification $qualy */
                foreach ($qualifications as $qualy){
                    if($qualy->getValue()){
                        $positiveReview++;
                    }
                }
                if($positiveReview >= $threshold){
                    try {
                        $shop->addBadge($qualification->getBadge(), true);
                        $this->logger->info("Added badge");
                        $em->flush();
                    }catch (PreconditionFailedException $e){
                        //do nothing
                    }

                }else{
                    try {
                        $shop->delBadge($qualification->getBadge());
                        $this->logger->info("Removed badge");
                        $em->flush();
                    }catch (PreconditionFailedException $e){
                        //do nothing
                    }

                }
            }else{
                //recalculate if settings changed
                if($shop->getBadges()->contains($qualification->getBadge())){
                    try {
                        $shop->delBadge($qualification->getBadge());
                        $this->logger->info("Removed badge because of less than min qualifications");
                        $em->flush();
                    }catch (PreconditionFailedException $e){
                        //do nothing
                    }
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