<?php

namespace App\FinancialApiBundle\EventSubscriber\Kernel;

use App\FinancialApiBundle\DependencyInjection\App\Commons\ChallengeHandler;
use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\NFTTransaction;
use App\FinancialApiBundle\Event\PurchaseSuccessEvent;
use App\FinancialApiBundle\Event\ShareNftEvent;
use App\FinancialApiBundle\Event\TransferEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SuccessPurchaseSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var DocumentManager $dm */
    private $dm;

    private $container;

    private $logger;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container){
        $this->em = $em;
        $this->container = $container;
        $this->dm = $container->get('doctrine_mongodb')->getManager();
        $this->logger = $this->container->get('logger');
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        // TODO: Implement getSubscribedEvents() method.
        return [
            PurchaseSuccessEvent::NAME => 'onSuccessPurchase',
            TransferEvent::NAME => 'onSuccessTransfer'
        ];
    }

    public function onSuccessPurchase(PurchaseSuccessEvent $event){
        if($this->getSetting()){
            $this->checkConstraintsAndDispatch(Challenge::ACTION_TYPE_BUY, $event);
        }

    }

    public function onSuccessTransfer(TransferEvent $event){
        if($this->getSetting()){
            $this->checkConstraintsAndDispatch(Challenge::ACTION_TYPE_SEND, $event);
        }
    }

    private function getSetting(){
        $setting = $this->em->getRepository(ConfigurationSetting::class)->findOneBy(array(
            'scope' => ConfigurationSetting::NFT_SCOPE,
            'name' => ConfigurationSetting::SETTING_C2B_CHALLENGES_STATUS,
            'value' => 'enabled'
        ));
        return $setting;
    }

    private function checkConstraintsAndDispatch($action, $event){
        //search by challenges with action
        $challenges = $this->em->getRepository(Challenge::class)->findBy(array(
            'status' => Challenge::STATUS_OPEN,
            'action' => $action
        ));

        $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: '.count($challenges). ' open challenges found');
        /** @var Challenge $challenge */
        foreach ($challenges as $challenge){
            $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: check challenge '.$challenge->getId());

            /** @var ChallengeHandler $challenge_handler */
            $challenge_handler = $this->container->get('net.app.commons.challenge_handler');


            [$totalAmount, $totalTransactions] = $challenge_handler->getChallengeTotals($event->getAccount(), $challenge);
            [$amount_required_constraint, $threshold_constraint] = $challenge_handler->checkConstraints($challenge, $totalAmount, $totalTransactions);

            if($threshold_constraint && $amount_required_constraint){
                if($challenge->getTokenReward()){
                    //dispatch NFT event
                    $this->dispatchEvent($challenge, $event);

                }
            }
        }
    }

    public function dispatchEvent(Challenge $challenge, $event){
        $dispatcher = $this->container->get('event_dispatcher');
        $nftEvent = new ShareNftEvent(
            $challenge,
            NFTTransaction::B2C_SHARABLE_CONTRACT,
            $challenge->getOwner(),
            $event->getAccount(),
            null
        );
        $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: dispatch ShareNftEvent');
        $dispatcher->dispatch(ShareNftEvent::NAME, $nftEvent);
    }
}