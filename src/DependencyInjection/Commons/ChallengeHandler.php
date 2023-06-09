<?php

namespace App\DependencyInjection\Commons;

use App\Document\Transaction;
use App\Entity\Challenge;
use App\Entity\Group;
use App\Financial\Currency;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

class ChallengeHandler
{
    private $doctrine;

    /** @var Logger $logger */
    private $logger;

    private $dm;

    public function __construct($doctrine, ContainerInterface $container,Logger $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->dm = $container->get('doctrine_mongodb')->getManager();
    }

    public function getChallengeTotals(Group $account, $challenge){
        $this->logger->info('CHALLENGE_HANDLER checking totals');
        $transactions = $this->dm->getRepository(Transaction::class)->findTransactions(
            $account,
            $challenge->getStartDate(),
            $challenge->getFinishDate(),
            '',
            'created',
            'DESC'
        );

        //check if this transactions sum more than amount required
        $totalAmount = 0;
        $totalTransactions = 0;
        $this->logger->info('CHALLENGE_HANDLER total tx found: '.count($transactions));
        foreach ($transactions as $transaction){
            //only out transactions count
            if($transaction->getType() === Transaction::$TYPE_OUT && $transaction->getInternal() === false && $transaction->getCurrency() !== Currency::$EUR){
                $this->logger->info('CHALLENGE_HANDLER checking tx');
                //check challenge constraints for every tx
                if($this->isChallengeAware($challenge, $transaction)){
                    $this->logger->info('CHALLENGE_HANDLER is challenge aware');
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
            $this->logger->info('CHALLENGE_HANDLER: total amount achieved');
        }

        if($totalTransactions >= $challenge->getThreshold()){
            //success constraint
            $threshold_constraint = true;
            $this->logger->info('CHALLENGE_HANDLER: total transactions achieved');
        }

        return [$amount_required_constraint, $threshold_constraint];
    }

    private function isChallengeAware(Challenge $challenge, Transaction $tx){
        $payOutInfo = $tx->getPayOutInfo();
        $receiver_id = $payOutInfo['receiver_id'];
        $em = $this->doctrine->getManager();
        /** @var Group $receiver */
        $receiver = $em->getRepository(Group::class)->find($receiver_id);
        $this->logger->info('CHALLENGE_HANDLER checking is challenge aware');
        switch ($challenge->getAction()){
            case Challenge::ACTION_TYPE_BUY:
                if($receiver->getType() === Group::ACCOUNT_TYPE_ORGANIZATION){
                    if(!$challenge->getBadges()->isEmpty()){
                        $this->logger->info('CHALLENGE_HANDLER challenge has badges');
                        if($this->accountIsBadgeAware($receiver, $challenge)){
                            $this->logger->info('CHALLENGE_HANDLER challenge is badges aware');
                            return true;
                        }
                    }else{
                        $this->logger->info('CHALLENGE_HANDLER challenge without badges');
                        return true;
                    }
                }
                break;
            case Challenge::ACTION_TYPE_SEND:
                if($receiver->getType() === Group::ACCOUNT_TYPE_PRIVATE){
                    $this->logger->info('CHALLENGE_HANDLER challenge send private');
                    return true;
                }
                break;
            default:
                $this->logger->info('CHALLENGE_HANDLER challenge default false');
                return false;
        }

        $this->logger->info('CHALLENGE_HANDLER challenge raised final return');
        return false;
    }

    private function accountIsBadgeAware(Group $account, Challenge $challenge){
        $isBadgeAware = false;
        foreach ($challenge->getBadges() as $challenge_badge){
            if($account->hasBadge($challenge_badge)) $isBadgeAware = true;
        }

        return $isBadgeAware;
    }

}