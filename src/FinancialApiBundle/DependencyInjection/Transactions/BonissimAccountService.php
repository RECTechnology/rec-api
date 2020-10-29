<?php
namespace App\FinancialApiBundle\DependencyInjection\Transactions;

use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UPCNotificator;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class BonissimAccountService {

    /** @var ContainerInterface $container */
    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function CreateBonissimAccount($user_id, $campaign_name, $amount=0){
        $em = $this->container->get('doctrine.orm.entity_manager');
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => $campaign_name]);
        $user = $em->getRepository(User::class)->findOneBy(['id' => $user_id]);

        if($user->getPrivateTosCampaign()){
            $account = new Group();
            $account->setName($campaign_name);
            /** @var FakeEasyBitcoinDriver $recDriver */
            $recDriver = $this->container->get('net.app.driver.easybitcoin.rec');
            $address = $recDriver->getnewaddress();
            $account->setRecAddress($address);
            $account->setMethodsList(['rec']);
            $account->setCif($user->getDNI());
            $account->setActive(true);
            $account->setEmail($user->getEmail());
            $account->setRoles([BaseApiV2Controller::ROLE_ORGANIZATION]);
            $account->setKycManager($user);
            $account->setType(Group::ACCOUNT_TYPE_PRIVATE);
            $account->setSubtype(Group::ACCOUNT_SUBTYPE_NORMAL);
            $account->setTier(1);
            $account->setRedeemableAmount(min($amount, $campaign->getMax()));

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
            $wallet->setCurrency('REC');
            $wallet->setAvailable(0);
            $wallet->setBalance(0);
            $wallet->setGroup($account);
            $wallets->add($wallet);
            $account->setWallets($wallets);

            $campaign_accounts = $campaign->getAccounts();
            $campaign_accounts->add($account);
            $campaign->setAccounts($campaign_accounts);

            $account_campaigns = $account->getCampaigns();
            $account_campaigns->add($campaign);
            $account->setCampaigns($account_campaigns);

            $em->persist($user);
            $em->persist($campaign);
            $em->persist($wallet);
            $em->persist($account);
            $em->persist($userAccount);
            $em->flush();
        }



    }
}