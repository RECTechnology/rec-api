<?php


namespace Test\FinancialApiBundle;


use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Client as OAuthClient;
use App\FinancialApiBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;

class TestDataFactory {

    /** @var Client $client */
    private $client;

    /**
     * TestDataFactory constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return OAuthClient
     */
    public function getOAuthClient(): OAuthClient {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        return $em->getRepository(OAuthClient::class)->findOneBy([]);
    }

    /**
     * @return User
     */
    public function getTestAdmin(): User {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        return $em->getRepository(User::class)->findOneBy(['username' => UserFixture::TEST_ADMIN_CREDENTIALS['username']]);
    }

    /**
     * @return User
     */
    public function getTestUser(): User {
        /** @var EntityManagerInterface $em */
        $em = $this->client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        return $em->getRepository(User::class)->findOneBy(['username' => UserFixture::TEST_USER_CREDENTIALS['username']]);
    }

}