<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\AccountCampaign;
use App\FinancialApiBundle\Entity\Balance;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;
use Doctrine\ORM\EntityManagerInterface;
use FOS\OAuthServerBundle\Util\Random;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BonusHandler{

    private $doctrine;

    private $clientGroup;

    private $shopGroup;

    private $originTx;

    private $flowHandler;

    private $bonissimService;

    /** @var Logger $logger */
    private $logger;

    private $cryptoCurrency;

    public function __construct($doctrine, $flowHandler, $bonissimService, Logger $logger, ContainerInterface $container){
        $this->doctrine = $doctrine;
        $this->flowHandler = $flowHandler;
        $this->bonissimService = $bonissimService;
        $this->logger = $logger;
        $this->cryptoCurrency = $container->getParameter("crypto_currency");
    }

    private function getEntityManager(){
        return $this->doctrine->getManager();
    }

    public function bonificateTx(Transaction $tx){
        $this->logger->info("BONUS HANDLER -> checking bonifications for tx -> ".$tx->getId()." - ".$tx->getMethod()."-".$tx->getType());
        $extra_data = [];
        $this->setUpBonificator($tx);
        if($this->isLTABBonificable()) $extra_data = $this->generateLTABBonification();

        if($this->isCultureBonificable()) $this->generateCultureBonification();

        if($this->isRedeemableLTAB()) $this->redeemTxLTAB();

        $this->manageV2Bonifications();
        return $extra_data;
    }

    private function setUpBonificator(Transaction $tx){
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $this->clientGroup = $em->getRepository(Group::class)->find($tx->getGroup());
        $this->originTx = $tx;
    }

    private function getTxDestination(): ?Group
    {
        $paymentInfo = $this->originTx->getPayOutInfo();
        $address = $paymentInfo['address'];

        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $destination = $em->getRepository(Group::class)
            ->findOneBy(['rec_address' => $address]);

        if(!$destination){
            // checking if the address belongs to an order
            $orderRepo = $em->getRepository(PaymentOrder::class);

            /** @var PaymentOrder $order */
            $order = $orderRepo->findOneBy(
                ['payment_address' => $address]
            );
            if($order){
                $destination = $order->getPos()->getAccount();
                return $destination;
            }
            else {
                return null;
            }
        }

        return $destination;

    }

    private function isLTABBonificable(){
        $em = $this->getEntityManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);

        if($this->originTx->getStatus() !== Transaction::$STATUS_SUCCESS) return false;

        if($this->originTx->getMethod() !== strtolower($this->cryptoCurrency)) return false;

        if($this->originTx->getType() !== Transaction::$TYPE_OUT) return false;

        //check if campaign is active
        if(!isset($campaign)) return false;
        $now = new \DateTime('NOW');
        if($now < $campaign->getInitDate() || $now > $campaign->getEndDate()) return false;

        if($campaign->getCampaignAccount() === $this->clientGroup->getId()) return false;

        if($this->clientGroup->getType() !== Group::ACCOUNT_TYPE_PRIVATE) return false;

        $accountRepo = $em->getRepository(Group::class);
        $client_campaigns = $accountRepo->find($this->clientGroup->getId())->getCampaigns();
        $shop = $this->getTxDestination();
        if(!$shop) return false;
        $shop_campaigns = $accountRepo->find($shop->getId())->getCampaigns();

        if(!$shop_campaigns->contains($campaign)) return false;

        if($shop->getType() !== Group::ACCOUNT_TYPE_ORGANIZATION) return false;

        //check if has at least one account in campaign
        if(!$this->getLtabAccount($this->originTx->getUser(), $campaign)) return false;
        //check if store and account are the same
        if($this->clientGroup->getKycManager()->getId() === $shop->getKycManager()->getId()) return false;
        $this->logger->info("BONUS HANDLER -> Tx is LTAB bonificable");
        return true;
    }

    private function isCultureBonificable(){
        $em = $this->getEntityManager();
        /** @var Campaign $campaign */
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::CULTURE_CAMPAIGN_NAME]);

        if(!$campaign->isBonusEnabled()) return false;
        if($this->originTx->getType() !== 'in') return false;
        if($this->originTx->getMethod() !== 'lemonway') return false;
        if($this->originTx->getStatus() !== Transaction::$STATUS_SUCCESS) return false;

        if(!isset($campaign)) return false;

        $now = new \DateTime('NOW');
        if($now < $campaign->getInitDate() || $now > $campaign->getEndDate()) return false;

        if($campaign->getCampaignAccount() === $this->clientGroup->getId()) return false;

        $clientCampaigns = $this->clientGroup->getCampaigns();
        if(!$clientCampaigns->contains($campaign)) return false;

        $rewarded_amount = $this->clientGroup->getRewardedAmount();
        $new_rewarded = min($this->originTx->getAmount() / 100, $campaign->getMax() - $rewarded_amount);
        if($new_rewarded <= 0) return false;

        $this->logger->info("BONUS HANDLER -> Tx is CULTURE bonificable");
        return true;
    }

    private function isRedeemableLTAB(){

        if($this->originTx->getType() !== 'in') return false;
        if($this->originTx->getMethod() !== 'lemonway') return false;
        if($this->originTx->getStatus() !== Transaction::$STATUS_SUCCESS) return false;
        $em = $this->getEntityManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($this->originTx->getUser());
        if(!$user->getPrivateTosCampaign()) return false;
        if($this->clientGroup->getType() !== Group::ACCOUNT_TYPE_PRIVATE) return false;

        //check if is ltab account? make sense? (if no ltab account require min amount)
        //importe mayor que el minimo
        /** @var Campaign $campaign */
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        if(!isset($campaign)) return false;
        $now = new \DateTime('NOW');
        if($now < $campaign->getInitDate() || $now > $campaign->getEndDate()) return false;
        if(!$campaign->isBonusEnabled()) return false;
        $ltabAccount = $this->getLtabAccount($this->originTx->getUser(), $campaign);
        if(!isset($ltabAccount) && $this->originTx->getAmount() / 100 < $campaign->getMin()) return false;
        //que no sea de culture
        $culture_campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::CULTURE_CAMPAIGN_NAME]);
        if (in_array($this->clientGroup, $culture_campaign->getAccounts()->getValues())) return false;
        $this->logger->info("BONUS HANDLER -> Tx is Redeemable");
        return true;
    }

    private function generateLTABBonification(){

        $em = $this->getEntityManager();
        /** @var Campaign $campaign */
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        $campaignAccountId = $campaign->getCampaignAccount();
        $campaignAccount = $em->getRepository(Group::class)->find($campaignAccountId);
        $ltabAccount = $this->getLtabAccount($this->originTx->getUser(), $campaign);
        $exchanger = $this->getExchanger($this->clientGroup->getId());
        //calcular el bonificable amount
        $bonificableAmount = $this->getBonificable($ltabAccount);

        $campaign_balance = $campaignAccount->getWallet($this->cryptoCurrency)->getBalance();
        $bonificationAmount =min($campaign_balance, round($bonificableAmount * $campaign->getRedeemablePercentage()/100,2)*1e8);
        if($bonificationAmount > 0){
            $this->logger->info("BONUS HANDLER -> Bonification amount -> ".$bonificationAmount);
            try{
                $this->flowHandler->sendRecsWithIntermediary($campaignAccount, $exchanger, $ltabAccount, $bonificationAmount);
                //QUItar redeemable y suamr al rewarded
                $ltabAccount->setRedeemableAmount($ltabAccount->getRedeemableAmount() - $bonificableAmount);
                $bonificatedAmount = $ltabAccount->getRewardedAmount();
                $ltabAccount->setRewardedAmount($bonificatedAmount + $bonificableAmount);
                $em->flush();
                $extra_data = ['rewarded_ltab' => $bonificationAmount];
            }catch (HttpException $e){
                $extra_data = [];
            }

            return $extra_data;
        }

        return [];
    }

    private function getBonificable(Group $ltabAccount){
        $redeemable_amount = $ltabAccount->getRedeemableAmount();
        $decimals = ($this->originTx->getMethod() === 'lemonway')?100:1e8;
        return min($redeemable_amount, $this->originTx->getAmount() / $decimals);
    }

    private function getBonificableV2(Campaign $campaign){
        $percentage = $campaign->getRedeemablePercentage();

        //bonificable_amount esta en cents de euro
        $bonificable_amount = $this->originTx->getAmount();

        //bonification amount esta en euros
        $bonificationAmount = $bonificable_amount/100 * $percentage/100;

        //check bonifications in all accounts
        $owned_accounts = $this->getEntityManager()->getRepository(Group::class)->findBy(array(
            'kyc_manager' => $this->clientGroup->getkycManager()
        ));

        //total bonificated esta en satoshis
        $totalBonificated = 0;
        foreach ($owned_accounts as $account){
            $campaign_account = $this->getEntityManager()->getRepository(AccountCampaign::class)->findOneBy(array(
                'account' => $account,
                'campaign' => $campaign
            ));

            if($campaign_account){
                $totalBonificated += $campaign_account->getAcumulatedBonus();
            }
        }

        //available_bonification esta en recs(scale=0)
        $available_bonification = $campaign->getMax()/1e8 - $totalBonificated/1e8;
        if($available_bonification < $bonificationAmount){
            $bonificationAmount = $available_bonification;
        }
        //se devuelve bonificationAmount en satoshis
        return $bonificationAmount*1e8;

    }

    private function generateCultureBonification(){
        $em = $this->getEntityManager();
        /** @var Campaign $campaign */
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::CULTURE_CAMPAIGN_NAME]);
        $campaignAccountId = $campaign->getCampaignAccount();
        /** @var Group $campaignAccount */
        $campaignAccount = $em->getRepository(Group::class)->find($campaignAccountId);
        $exchanger = $this->getExchanger($this->clientGroup->getId());

        $rewarded_amount = $this->clientGroup->getRewardedAmount();
        $new_rewarded = min($this->originTx->getAmount() / 100, $campaign->getMax() - $rewarded_amount);
        $satoshi_decimals = 1e8;
        $bonificableAmount = round(($new_rewarded * $satoshi_decimals) / 100 * $campaign->getRedeemablePercentage(), -6);

        $campaignAccountWallet = $campaignAccount->getWallet($this->cryptoCurrency);
        if($bonificableAmount > $campaignAccountWallet->getBalance()){
            //if we have to send more than the current balance only send remaining balance
            $bonificableAmount = $campaignAccountWallet->getBalance();
        }

        $this->logger->info("BONUS HANDLER -> Bonification amount -> ".$bonificableAmount);
        try{
            $this->flowHandler->sendRecsWithIntermediary($campaignAccount, $exchanger, $this->clientGroup, $bonificableAmount, 'Bonificació Cultural +' . $campaign->getRedeemablePercentage() . '%');
        }catch (HttpException $e){
            //Do something here
        }
        $this->clientGroup->setRewardedAmount($new_rewarded + $rewarded_amount);
    }

    private function getExchanger($receiver_id): Group
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $kyc2_id = $em->getRepository(Tier::class)->findOneBy(array('code' => 'KYC2'));

        $exchangers = $em->getRepository(Group::class)->findBy([
            'type' => 'COMPANY',
            'level' => $kyc2_id->getId(),
            'active' => 1]);

        if (count($exchangers) == 0) {
            throw new HttpException(403, '"No qualified exchanger found.');
        }

        $culture_campaign = $em->getRepository(Campaign::class)->findOneBy(array('name' => Campaign::CULTURE_CAMPAIGN_NAME));
        $receiver_account = $em->getRepository(Group::class)->find($receiver_id);

        if (in_array($culture_campaign, $receiver_account->getCampaigns()->getValues())) {
            $valid_exchangers = [];
            foreach ($exchangers as $exchanger) {
                if (in_array($culture_campaign, $exchanger->getCampaigns()->getValues())) {
                    array_push($valid_exchangers, $exchanger);
                }
            }
            if (count($valid_exchangers) == 0) {
                throw new HttpException(403, 'Exchanger in campaign not found.');
            }
            $exchangerAccount = $valid_exchangers[random_int(0, count($valid_exchangers) - 1)];
        } else {
            $exchangerAccount = $exchangers[random_int(0, count($exchangers) - 1)];
        }
        return  $exchangerAccount;
    }

    private function getExchangerV2()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $kyc2_id = $em->getRepository(Tier::class)->findOneBy(array('code' => 'KYC2'));

        $exchangers = $em->getRepository(Group::class)->findBy([
            'type' => 'COMPANY',
            'level' => $kyc2_id->getId(),
            'active' => 1]);

        if (count($exchangers) == 0) {
            throw new HttpException(403, '"No qualified exchanger found.');
        }

        return $exchangers[random_int(0, count($exchangers) - 1)];
    }

    private function getLtabAccount($user_id, Campaign $campaign, $force_creation = false){
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $accountRepo = $em->getRepository(Group::class);
        $user_private_accounts = $accountRepo->findBy(['kyc_manager' => $user_id, 'type' => Group::ACCOUNT_TYPE_PRIVATE]);
        foreach ($user_private_accounts as $account) {
            if ($account->getCampaigns()->contains($campaign)) {
                return $account;
            }
        }

        //create ltab account because doesnt exist
        if($force_creation) return $this->createLtabAccount();

        return null;
    }

    private function createLtabAccount(){

        $user_id = $this->clientGroup->getKycManager()->getId();
        $this->logger->info("BONUS HANDLER -> Creating LTAB account for user ".$user_id);
        //We create the account always with redeemable 0.
        $account = $this->bonissimService->CreateCampaignAccountV2($user_id,
            Campaign::BONISSIM_CAMPAIGN_NAME, 0);

        return $account;

    }

    private function redeemTxLTAB(){

        $em = $this->getEntityManager();

        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        $ltabAccount = $this->getLtabAccount($this->originTx->getUser(),$campaign, true);

        //TODO extract to generic method
        $old_redeemable = $ltabAccount->getRedeemableAmount();
        $allowed_redeemable = min($this->originTx->getAmount()/100, $campaign->getMax() -
            ($ltabAccount->getRewardedAmount() + $old_redeemable)) + $old_redeemable;

        $ltabAccount->setRedeemableAmount($allowed_redeemable);
        $this->logger->info("BONUS HANDLER -> Write redemable -> ".$allowed_redeemable);

        $em->flush();

    }

    /**
     * Manage bonifications v2, only recharges for now
     */
    private function manageV2Bonifications(){
        $this->logger->info("BONUS HANDLER -> manage bonifications v2");
        //only success can access for recharge, if we allow other kind of campaigns we should update this part
        if($this->originTx->getStatus() === Transaction::$STATUS_SUCCESS && $this->originTx->getMethod() === 'lemonway'){
            $active_campaigns = $this->getEntityManager()->getRepository(Campaign::class)->findBy(array('status' => Campaign::STATUS_ACTIVE, 'version' => 2));

            foreach ($active_campaigns as $active_campaign){
                if($this->isCampaignV2Bonificable($active_campaign)){
                    $this->logger->info("BONUS HANDLER -> create bonification v2 for campaign -> ".$active_campaign->getName());
                    $this->createV2Bonification($active_campaign);
                }
            }
        }

    }

    private function createV2Bonification(Campaign $campaign){
        $campaignAccountId = $campaign->getCampaignAccount();
        $campaignAccount = $this->getEntityManager()->getRepository(Group::class)->find($campaignAccountId);
        $exchanger = $this->getExchangerV2();

        $bonificableAmount = $this->getBonificableV2($campaign);

        if($bonificableAmount > 0){
            try{
                $this->logger->info("BONUS HANDLER -> send recs with intermediary");
                $this->flowHandler->sendRecsWithIntermediary($campaignAccount, $exchanger, $this->clientGroup, $bonificableAmount, 'Bonificació +' . $campaign->getRedeemablePercentage() . '%', true, $campaign->getId());
                $accountCampaign = $this->getAccountCampaign($this->clientGroup, $campaign);
                if($accountCampaign){
                    $accountCampaign->setAcumulatedBonus($accountCampaign->getAcumulatedBonus() + $bonificableAmount);

                    $this->getEntityManager()->flush();
                }

            }catch (HttpException $e){

            }
        }else{
            $this->logger->info("BONUS HANDLER -> bonificable amount < 0 -> No-Bonification");
        }

    }

    private function isCampaignV2Bonificable(Campaign $campaign){
        if(!$this->isAccountInCampaign($this->clientGroup, $campaign)) return false;
        if($this->originTx->getAmount() < $campaign->getMin()/1e6) return false;
        if(!$campaign->isBonusEnabled()) return false;
        //only private accounts
        if($this->clientGroup->getType() === Group::ACCOUNT_TYPE_ORGANIZATION) return false;
        $this->logger->info("BONUS HANDLER -> tx is bonificable");
        return true;
    }

    private function isAccountInCampaign(Group $account, Campaign $campaign){
        $account_campaign = $this->getAccountCampaign($account, $campaign);

        if($account_campaign) {
            $this->logger->info("BONUS HANDLER -> account in campaign");
            return true;
        }
        $this->logger->info("BONUS HANDLER -> account NOT in campaign");

        return false;

    }

    /**
     * returns AccountCampaign Object
     */
    private function getAccountCampaign(Group $account, Campaign $campaign) :?AccountCampaign
    {
        return $this->getEntityManager()->getRepository(AccountCampaign::class)->findOneBy(array(
            'account' => $account,
            'campaign' => $campaign
        ));
    }

    private function isReceiverStore(){
        /** @var Group $destination */
        $destination = $this->getTxDestination();

        if($destination->getType() === Group::ACCOUNT_TYPE_ORGANIZATION) {
            return true;
        }

        return false;

    }

}
