<?php


namespace App\FinancialApiBundle\DataFixture;

use App\FinancialApiBundle\Entity\Client;
use App\FinancialApiBundle\Entity\Group as Account;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;

/**
 * Class ClientFixture
 * @package App\FinancialApiBundle\DataFixture
 */
class OAuthClientFixture extends Fixture implements DependentFixtureInterface {

    /** @var ClientManagerInterface $clientManager */
    private $clientManager;

    /**
     * OAuthClientFixture constructor.
     * @param ClientManagerInterface $clientManager
     */
    public function __construct(ClientManagerInterface $clientManager)
    {
        $this->clientManager = $clientManager;
    }


    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager) {
        /** @var Client $client */
        $client = $this->clientManager->createClient();
        $client->setRedirectUris([]);
        $client->setAllowedGrantTypes(['token', 'authorization_code', 'password', 'client_credentials', 'refresh_token']);
        $account = $manager->getRepository(Account::class)->findOneBy([]);
        $client->setGroup($account);
        $this->clientManager->updateClient($client);
        //client for admin panel in same account
        /** @var Client $clientAdmin */
        $clientAdmin = $this->clientManager->createClient();
        $clientAdmin->setRedirectUris([]);
        $clientAdmin->setAllowedGrantTypes(['token', 'authorization_code', 'password', 'client_credentials', 'refresh_token']);
        $clientAdmin->setGroup($account);
        $this->clientManager->updateClient($clientAdmin);

    }

    public function getDependencies(){
        return [
            AccountFixture::class,
        ];
    }
}