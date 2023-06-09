<?php

namespace App\EventSubscriber\Kernel;

use App\DependencyInjection\Commons\ChallengeHandler;
use App\Document\Transaction;
use App\Entity\AccountCampaign;
use App\Entity\Campaign;
use App\Entity\Challenge;
use App\Entity\ConfigurationSetting;
use App\Entity\Group;
use App\Entity\NFTTransaction;
use App\Event\PurchaseSuccessEvent;
use App\Event\ShareNftEvent;
use App\Event\TransferEvent;
use App\Financial\Currency;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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

    public function __construct(EntityManagerInterface $em, ContainerInterface $container, LoggerInterface $logger){
        $this->em = $em;
        $this->container = $container;
        $this->dm = $container->get('doctrine_mongodb')->getManager();
        $this->logger = $logger;
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
        if($event->getTransaction()->getCurrency() !== Currency::$EUR){
            if($this->getSetting()){
                $this->checkConstraintsAndDispatch(Challenge::ACTION_TYPE_BUY, $event);
            }

            $this->addSpentToAccountCampaign($event->getAccount(), $event->getTransaction());
        }

    }

    public function onSuccessTransfer(TransferEvent $event){
        if($event->getTransaction()->getCurrency() !== Currency::$EUR){
            if($this->getSetting()){
                $this->checkConstraintsAndDispatch(Challenge::ACTION_TYPE_SEND, $event);
            }
            $this->transferAcumulatedBonus($event->getAccount(), $event->getTransaction());
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
                    $this->dispatchEvent($challenge, $event, $totalTransactions, $totalAmount);

                }
            }
        }
    }

    public function dispatchEvent(Challenge $challenge, $event, $totalTransactions, $totalAmount){
        $dispatcher = $this->container->get('event_dispatcher');
        $nftEvent = new ShareNftEvent(
            $challenge,
            NFTTransaction::B2C_SHARABLE_CONTRACT,
            $challenge->getOwner(),
            $event->getAccount(),
            null,
            $totalTransactions,
            $totalAmount
        );
        $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: dispatch ShareNftEvent');
        $dispatcher->dispatch($nftEvent, ShareNftEvent::NAME);
    }

    private function addSpentToAccountCampaign(Group $account, Transaction $transaction): void
    {
        $active_campaigns = $this->getActiveV2Campaigns();
        $amount = $transaction->getAmount();
        foreach ($active_campaigns as $active_campaign){
            /** @var AccountCampaign $account_campaign */
            $account_campaign = $this->em->getRepository(AccountCampaign::class)->findOneBy(array('account' => $account, 'campaign' => $active_campaign));
            if($account_campaign){
                $available_bonus = $account_campaign->getAcumulatedBonus() - $account_campaign->getSpentBonus();
                if($available_bonus > 0){
                    if($available_bonus > $amount){
                        //total spent
                        $account_campaign->setSpentBonus($account_campaign->getSpentBonus() + $amount);
                        $this->em->flush();
                        break;
                    }

                    //partial spent
                    $account_campaign->setSpentBonus($account_campaign->getSpentBonus() + $available_bonus);
                    $this->em->flush();
                    $amount -= $available_bonus;
                }
            }


        }

    }

    private function transferAcumulatedBonus(Group $account, Transaction $transaction){
        $pay_out_info  = $transaction->getPayOutInfo();
        $destination_address = $pay_out_info['address'];
        //get receiver
        /** @var Group $destination */
        $destination = $this->em->getRepository(Group::class)->findOneBy(array('rec_address' =>$destination_address));

        //check if is making transfer between accounts with same kyc_manager
        if($destination && $destination->getKycManager() === $account->getKycManager()){
            $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: transfer accumulated bonus between accounts with same manager , '.$account->getId().' to '.$destination->getId());
            $destination_active_account_campaign = $this->getDestinationActiveAccountCampaign($destination);

            if($destination_active_account_campaign){
                $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: transfer accumulated bonus-> destination has account in campaign');
                $account_campaigns = $this->em->getRepository(AccountCampaign::class)->findBy(array('account' => $account));
                $amount = $transaction->getAmount();
                /** @var AccountCampaign $account_campaign */
                foreach ($account_campaigns as $account_campaign){
                    //check if campaigns is active
                    if($account_campaign->getCampaign()->getStatus() === Campaign::STATUS_ACTIVE){
                        $diff_acumulated_spent = $account_campaign->getAcumulatedBonus() - $account_campaign->getSpentBonus();
                        if($diff_acumulated_spent > $amount){
                            $account_campaign->setAcumulatedBonus($account_campaign->getAcumulatedBonus() - $amount);
                            $destination_active_account_campaign->setAcumulatedBonus($destination_active_account_campaign->getAcumulatedBonus() + $amount);
                            $this->em->flush();
                            break;
                        }

                        $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: transfer accumulated bonus-> sending more amount than bonification accumulated imn this account');
                        $account_campaign->setAcumulatedBonus($account_campaign->getAcumulatedBonus() - $diff_acumulated_spent);
                        $destination_active_account_campaign->setAcumulatedBonus($destination_active_account_campaign->getAcumulatedBonus() + $diff_acumulated_spent);
                        $this->em->flush();
                        $amount -= $diff_acumulated_spent;
                    }

                }
            }else{
                $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: transfer accumulated bonus-> destination not in campaign');
            }

        }

    }

    private function getActiveV2Campaigns(){
        return $this->em->getRepository(Campaign::class)->getActiveCampaignsV2();
    }

    private function getDestinationActiveAccountCampaign(Group $destination){
        $destination_campaigns = $this->em->getRepository(AccountCampaign::class)->findBy(array('account' => $destination));

        if(count($destination_campaigns) > 0){
            foreach ($destination_campaigns as $destination_campaign){
                if($destination_campaign->getCampaign()->getStatus() === Campaign::STATUS_ACTIVE){
                    return $destination_campaign;
                }
            }
        }

        return null;
    }
}