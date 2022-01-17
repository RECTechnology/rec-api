<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Document\Transaction;
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BonusHandler{

    private $doctrine;

    private $clientGroup;

    private $shopGroup;

    private $originTx;

    private $flowHandler;

    private $bonissimService;

    public function __construct($doctrine, $flowHandler, $bonissimService){
        $this->doctrine = $doctrine;
        $this->flowHandler = $flowHandler;
        $this->bonissimService = $bonissimService;
    }

    private function getEntityManager(){
        return $this->doctrine->getManager();
    }
        //todo separate code (duplicated calls)
    public function bonificateTx(Transaction $tx){
        $extra_data = [];
        $this->setUpBonificator($tx);
        if($this->isLTABBonificable()) $extra_data = $this->generateLTABBonification();

        if($this->isCultureBonificable()) $this->generateCultureBonification();

        if($this->isRedeemableLTAB()) $this->redeemTxLTAB();
        return $extra_data;
    }

    private function setUpBonificator(Transaction $tx){
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $this->clientGroup = $em->getRepository(Group::class)->find($tx->getGroup());
        $this->originTx = $tx;
    }

    private function getTxDestination(){
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
                ['payment_address' => $address, 'status' => PaymentOrder::STATUS_IN_PROGRESS]
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

        if($this->originTx->getMethod() !== 'rec') return false;

        if($this->originTx->getType() !== 'out') return false;

        //TODO check if campaign is active
        if(!isset($campaign)) return false;
        $now = new \DateTime('NOW');
        if($now < $campaign->getInitDate() || $now > $campaign->getEndDate()) return false;

        if($campaign->getCampaignAccount() === $this->clientGroup->getId()) return false;

        if($this->clientGroup->getType() !== Group::ACCOUNT_TYPE_PRIVATE) return false;

        $accountRepo = $em->getRepository(Group::class);
        $client_campaigns = $accountRepo->find($this->clientGroup->getId())->getCampaigns();
        $shop = $this->getTxDestination();
        $shop_campaigns = $accountRepo->find($shop->getId())->getCampaigns();

        if(!$shop_campaigns->contains($campaign)) return false;

        if($shop->getType() !== Group::ACCOUNT_TYPE_ORGANIZATION) return false;

        //check if has at least one account in campaign
        if(!$this->getLtabAccount($this->originTx->getUser(), $campaign)) return false;
        //TODO check if store and account are the same
        if($this->clientGroup->getKycManager()->getId() === $shop->getKycManager()->getId()) return false;
        return true;
    }

    private function isCultureBonificable(){
        $em = $this->getEntityManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::CULTURE_CAMPAIGN_NAME]);

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

        //TODO check if is ltab account? make sense? (if no ltab account require min amount)
        //TODO importe mayor que el minimo
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        $ltabAccount = $this->getLtabAccount($this->originTx->getUser(), $campaign);
        if(!isset($ltabAccount) && $this->originTx->getAmount() / 100 < $campaign->getMin()) return false;
        //TODO que no sea de culñture
        $culture_campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::CULTURE_CAMPAIGN_NAME]);
        if (in_array($this->clientGroup, $culture_campaign->getAccounts()->getValues())) return false;
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
        //TODO calcular el bonificable amount
        $bonificableAmount = $this->getBonificable($ltabAccount);

        $campaign_balance = $campaignAccount->getWallet('REC')->getBalance();
        $bonificationAmount =min($campaign_balance, round($bonificableAmount * $campaign->getRedeemablePercentage()/100,2)*1e8);
        if($bonificationAmount > 0){
            $this->flowHandler->sendRecsWithIntermediary($campaignAccount, $exchanger, $ltabAccount, $bonificationAmount);
            //QUItar redeemable y suamr al rewarded
            $ltabAccount->setRedeemableAmount($ltabAccount->getRedeemableAmount() - $bonificableAmount);
            $bonificatedAmount = $ltabAccount->getRewardedAmount();
            $ltabAccount->setRewardedAmount($bonificatedAmount + $bonificableAmount);
            $em->flush();
            //TODO return extra_data;
            $extra_data = ['rewarded_ltab' => $bonificationAmount];
            return $extra_data;
        }else{
            return [];
        }
    }

    private function getBonificable(Group $ltabAccount){
        $redeemable_amount = $ltabAccount->getRedeemableAmount();
        $decimals = ($this->originTx->getMethod() === 'lemonway')?100:1e8;
        return min($redeemable_amount, $this->originTx->getAmount() / $decimals);


    }

    private function generateCultureBonification(){
        $em = $this->getEntityManager();
        /** @var Campaign $campaign */
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::CULTURE_CAMPAIGN_NAME]);
        $campaignAccountId = $campaign->getCampaignAccount();
        $campaignAccount = $em->getRepository(Group::class)->find($campaignAccountId);
        $exchanger = $this->getExchanger($this->clientGroup->getId());

        $rewarded_amount = $this->clientGroup->getRewardedAmount();
        $new_rewarded = min($this->originTx->getAmount() / 100, $campaign->getMax() - $rewarded_amount);
        $satoshi_decimals = 1e8;
        $bonificableAmount = round(($new_rewarded * $satoshi_decimals) / 100 * $campaign->getRedeemablePercentage(), -6);

        $this->flowHandler->sendRecsWithIntermediary($campaignAccount, $exchanger, $this->clientGroup, $bonificableAmount);
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

        $em->flush();

    }

}