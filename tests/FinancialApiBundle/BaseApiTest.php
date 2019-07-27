<?php

namespace Test\FinancialApiBundle;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use FOS\OAuthServerBundle\Controller\TokenController;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class BaseApiTest extends WebTestCase {

    /**
     * @return Client
     */
    protected function getApiClient(){
        $client = static::createClient();
        $client->setServerParameters(
            [
                'HTTP_Content-Type' => 'application/json',
                'HTTP_Accept' => 'application/json'
            ]
        );
        return $client;
    }

    public function testDummy(){
        static::assertTrue(true);
    }

    protected function getUserClient(){

    }

    protected function getManagerClient(){

    }

    protected function getAdminClient(){
        $client = $this->getApiClient();
        self::assertContains(1, []);
        return $client;
    }

    /**
     * @param Client $client
     */
    protected function logIn(Client $client, $oauthCredentials){
        $client->request('POST', '/oauth/v2/token', null, null, null, []);
    }

    /**
     * @param Client $client
     * @throws ToolsException
     * @throws \Exception
     */
    protected function clearDatabase(Client $client){

        $application = new Application($client->getKernel());
        $application->setAutoExit(false);

        $application->run(
            new ArrayInput(['command' => 'doctrine:database:create', '--if-not-exists']),
            new NullOutput()
        );

        /** @var EntityManagerInterface $em */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($em);
        $tool->dropSchema($metaData);
        $tool->createSchema($metaData);
    }

    /**
     * @param Client $client
     * @throws \Exception
     */
    protected function loadFixtures(Client $client){
        $application = new Application($client->getKernel());
        $application->setAutoExit(false);

        $application->run(
            new ArrayInput(['command' => 'doctrine:fixtures:load']),
            new NullOutput()
        );
    }

    /**
     * @throws ToolsException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $client = $this->getApiClient();
        $this->clearDatabase($client);
        $this->loadFixtures($client);
    }
}
