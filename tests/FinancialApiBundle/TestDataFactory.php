<?php


namespace Test\FinancialApiBundle;


use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Client as OAuthClient;
use App\FinancialApiBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

trait TestDataFactory {

    /**
     * @return OAuthClient
     */
    public function getOAuthClient(): OAuthClient {
        /** @var EntityManagerInterface $em */
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        return $em->getRepository(OAuthClient::class)->findOneBy([]);
    }

    /**
     * @return OAuthClient
     */
    public function getOAuthClientAdminPanel(): OAuthClient {
        /** @var EntityManagerInterface $em */
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        return $em->getRepository(OAuthClient::class)->find(2);
    }

    /**
     * @return User
     */
    public function getTestAdmin(): User {
        /** @var EntityManagerInterface $em */
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        return $em->getRepository(User::class)->findOneBy(['username' => UserFixture::TEST_ADMIN_CREDENTIALS['username']]);
    }

    /**
     * @return User
     */
    public function getTestUser(): User {
        /** @var EntityManagerInterface $em */
        $em = self::createClient()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        return $em->getRepository(User::class)->findOneBy(['username' => UserFixture::TEST_USER_CREDENTIALS['username']]);
    }

}