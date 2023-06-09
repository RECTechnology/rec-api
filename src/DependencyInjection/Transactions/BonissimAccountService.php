<?php
namespace App\DependencyInjection\Transactions;

use App\Controller\BaseApiV2Controller;
use App\Entity\Campaign;
use App\Entity\Group;
use App\Entity\KYC;
use App\Entity\Tier;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserWallet;
use App\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BonissimAccountService {

    /** @var ContainerInterface $container */
    private $container;

    private $crypto_currency;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->crypto_currency = $container->getParameter('crypto_currency');
    }


    public function CreateCampaignAccount($user_id, $campaign_name, $amount=0){
        $this->createAccountStuff($user_id, $campaign_name, $amount);
    }

    public function CreateCampaignAccountV2($user_id, $campaign_name, $amount=0){
        return $this->createAccountStuff($user_id, $campaign_name, $amount);
    }

    private function createAccountStuff($user_id, $campaign_name, $amount=0){
        $em = $this->container->get('doctrine.orm.entity_manager');
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => $campaign_name]);
        $user = $em->getRepository(User::class)->findOneBy(['id' => $user_id]);
        $tos = false;
        if($campaign_name == Campaign::BONISSIM_CAMPAIGN_NAME){
            $tos = $user->getPrivateTosCampaign();
        }
        if($campaign_name == Campaign::CULTURE_CAMPAIGN_NAME){
            $tos = $user->isPrivateTosCampaignCulture();
        }
        if($tos){
            $account = new Group();
            $account->setName($campaign_name);
            /** @var FakeEasyBitcoinDriver $recDriver */
            $recDriver = $this->container->get('net.app.driver.easybitcoin.rec');
            $address = $recDriver->getnewaddress();
            $account->setRecAddress($address);
            $account->setMethodsList([strtolower($this->crypto_currency)]);
            $account->setCif($user->getDNI());
            $account->setActive(true);
            $account->setEmail($user->getEmail());
            $account->setRoles([BaseApiV2Controller::ROLE_ORGANIZATION]);
            $account->setKycManager($user);
            $account->setType(Group::ACCOUNT_TYPE_PRIVATE);
            $account->setSubtype(Group::ACCOUNT_SUBTYPE_NORMAL);
            $account->setTier(1);
            $account->setRedeemableAmount(min($amount, $campaign->getMax()));
            $level = $em->getRepository(Tier::class)->findOneBy(['code' => Tier::KYC_LEVELS[1]]);
            $account->setLevel($level);
            $image = $campaign->getImageUrl();
            $account->setCompanyImage($image);

            $userAccount = new UserGroup();
            $userAccount->setGroup($account);
            $userAccount->setUser($user);
            $userAccount->setRoles(['ROLE_ADMIN']); //User is admin in the account

            if(!$user->getKycValidations()) {
                $kyc = new KYC();
                $kyc->setUser($user);
                $kyc->setName($user->getName());
                $kyc->setEmail($user->getEmail());
                $em->persist($kyc);
            }


            $wallets = new ArrayCollection();

            $wallet = new UserWallet();
            $wallet->setCurrency($this->crypto_currency);
            $wallet->setAvailable(0);
            $wallet->setBalance(0);
            $wallet->setGroup($account);
            $wallets->add($wallet);
            $em->persist($wallet);

            $wallet = new UserWallet();
            $wallet->setCurrency('EUR');
            $wallet->setAvailable(0);
            $wallet->setBalance(0);
            $wallet->setGroup($account);
            $wallets->add($wallet);
            $em->persist($wallet);

            $account->setWallets($wallets);

            $campaign_accounts = $campaign->getAccounts();
            $campaign_accounts->add($account);
            $campaign->setAccounts($campaign_accounts);

            $account_campaigns = $account->getCampaigns();
            $account_campaigns->add($campaign);
            $account->setCampaigns($account_campaigns);

            $em->persist($user);
            $em->persist($campaign);

            $em->persist($account);
            $em->persist($userAccount);
            $em->flush();

            return $account;
        }
    }
}