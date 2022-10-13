<?php

namespace App\FinancialApiBundle\EventSubscriber\Kernel;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\NFTTransaction;
use App\FinancialApiBundle\Entity\TokenReward;
use App\FinancialApiBundle\Event\PurchaseSuccessEvent;
use App\FinancialApiBundle\Event\ShareNftEvent;
use App\FinancialApiBundle\Event\TransferEvent;
use Doctrine\Common\EventSubscriber;
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

    public function __construct(EntityManagerInterface $em, ContainerInterface $container){
        $this->em = $em;
        $this->container = $container;
        $this->dm = $container->get('doctrine_mongodb')->getManager();
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
        $logger = $this->container->get('logger');
        //search by challenges with action
        $challenges = $this->em->getRepository(Challenge::class)->findBy(array(
            'status' => Challenge::STATUS_OPEN,
            'action' => $action
        ));

        $logger->info('SUCCESS_PURCHASE_SUBSCRIBER: '.count($challenges). ' open challenges found');
        /** @var Challenge $challenge */
        foreach ($challenges as $challenge){
            $logger->info('SUCCESS_PURCHASE_SUBSCRIBER: check challenge '.$challenge->getId());
            //check constraints
            //get all transactions between 2 dates
            $transactions = $this->dm->getRepository(Transaction::class)->findTransactions(
                $event->getAccount(),
                $challenge->getStartDate(),
                $challenge->getFinishDate(),
                '',
                'created',
                'DESC'
            );

            $logger->info('SUCCESS_PURCHASE_SUBSCRIBER: '.count($transactions). ' transactions found for this account');

            $amount_required_constraint = false;
            $threshold_constraint = false;
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

            if($totalAmount >= $challenge->getAmountRequired()){
                //constraint success
                $amount_required_constraint = true;
                $logger->info('SUCCESS_PURCHASE_SUBSCRIBER: total amount achieved');
            }

            if($totalTransactions >= $challenge->getThreshold()){
                //success constraint
                $threshold_constraint = true;
                $logger->info('SUCCESS_PURCHASE_SUBSCRIBER: total transactions achieved');
            }

            if($threshold_constraint && $amount_required_constraint){
                if($challenge->getTokenReward()){

                    $dispatcher = $this->container->get('event_dispatcher');
                    $nftEvent = new ShareNftEvent(
                        $challenge,
                        NFTTransaction::B2C_SHARABLE_CONTRACT,
                        $challenge->getOwner(),
                        $event->getAccount(),
                        null
                    );
                    $logger->info('SUCCESS_PURCHASE_SUBSCRIBER: dispatch ShareNftEvent');
                    $dispatcher->dispatch(ShareNftEvent::NAME, $nftEvent);

                }
            }
        }
    }

    public function isChallengeAware(Challenge $challenge, Transaction $tx){
        $payOutInfo = $tx->getPayOutInfo();
        $receiver_id = $payOutInfo['receiver_id'];
        /** @var Group $receiver */
        $receiver = $this->em->getRepository(Group::class)->find($receiver_id);
        switch ($challenge->getAction()){
            case Challenge::ACTION_TYPE_BUY:
                if($receiver->getType() === Group::ACCOUNT_TYPE_ORGANIZATION){
                    return true;
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
    }
}