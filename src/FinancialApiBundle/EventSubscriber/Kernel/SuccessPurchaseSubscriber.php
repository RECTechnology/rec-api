<?php

namespace App\FinancialApiBundle\EventSubscriber\Kernel;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
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

            [$totalAmount, $totalTransactions] = $this->getChallengeTotals($event, $challenge);
            [$amount_required_constraint, $threshold_constraint] = $this->checkConstraints($challenge, $totalAmount, $totalTransactions);

            if($threshold_constraint && $amount_required_constraint){
                if($challenge->getTokenReward()){
                    //dispatch NFT event
                    $this->dispatchEvent($challenge, $event);

                }
            }
        }
    }

    public function isChallengeAware(Challenge $challenge, Transaction $tx){
        $payOutInfo = $tx->getPayOutInfo();
        $receiver_id = $payOutInfo['receiver_id'];
        /** @var Group $receiver */
        $receiver = $this->em->getRepository(Group::class)->find($receiver_id);
        $receiver->getBadges();
        switch ($challenge->getAction()){
            case Challenge::ACTION_TYPE_BUY:
                if($receiver->getType() === Group::ACCOUNT_TYPE_ORGANIZATION){
                    if($challenge->getBadges()){
                        if($this->accountIsBadgeAware($receiver, $challenge)){
                            return true;
                        }
                    }else{
                        return true;
                    }
                }
                break;
            case Challenge::ACTION_TYPE_SEND:
                if($receiver->getType() === Group::ACCOUNT_TYPE_PRIVATE){
                    return true;
                }
                break;
            default:
                return false;
        }

        return false;
    }

    public function accountIsBadgeAware(Group $account, Challenge $challenge){
        $isBadgeAware = false;
        foreach ($challenge->getBadges() as $challenge_badge){
            if($account->hasBadge($challenge_badge)) $isBadgeAware = true;
        }

        return $isBadgeAware;
    }

    public function getChallengeTotals($event, $challenge){
        $transactions = $this->dm->getRepository(Transaction::class)->findTransactions(
            $event->getAccount(),
            $challenge->getStartDate(),
            $challenge->getFinishDate(),
            '',
            'created',
            'DESC'
        );

        //check if this transactions sum more than amount required
        $totalAmount = 0;
        $totalTransactions = 0;
        foreach ($transactions as $transaction){
            //only out transactions count
            if($transaction->getType() === Transaction::$TYPE_OUT){
                //check challenge constraints for every tx
                if($this->isChallengeAware($challenge, $transaction)){
                    $totalAmount+=$transaction->getAmount();
                    $totalTransactions++;
                }
            }

        }

        return [$totalAmount, $totalTransactions];
    }

    public function checkConstraints(Challenge $challenge, $totalAmount, $totalTransactions){
        $amount_required_constraint = false;
        $threshold_constraint = false;

        if($totalAmount >= $challenge->getAmountRequired()){
            //constraint success
            $amount_required_constraint = true;
            $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: total amount achieved');
        }

        if($totalTransactions >= $challenge->getThreshold()){
            //success constraint
            $threshold_constraint = true;
            $this->logger->info('SUCCESS_PURCHASE_SUBSCRIBER: total transactions achieved');
        }

        return [$amount_required_constraint, $threshold_constraint];
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