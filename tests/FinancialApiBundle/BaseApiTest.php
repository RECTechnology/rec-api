<?php

namespace Test\FinancialApiBundle;

use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use FOS\OAuthServerBundle\Controller\TokenController;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;

abstract class BaseApiTest extends WebTestCase {

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
        $this->createDatabase($client);
    }


    /**
     * @param Client $client
     * @throws \Exception
     */
    protected function createDatabase(Client $client){
        $this->runCommand($client, 'doctrine:database:create', ['--if-not-exists']);
        $this->runCommand($client, 'doctrine:schema:create');
    }

    /**
     * @param Client $client
     * @param string $command
     * @param array $args
     * @return string
     * @throws \Exception
     */
    protected function runCommand(Client $client, string $command, array $args = []){
        $application = new Application($client->getKernel());
        $application->setAutoExit(false);
        $fullCommand = array_merge(['command' => $command], $args);
        $output = new BufferedOutput();
        $application->run(new ArrayInput($fullCommand), $output);

        $application->setCatchExceptions(false);
        return $output->fetch();
    }

    /**
     * @param Client $client
     * @throws \Exception
     */
    protected function loadFixtures(Client $client){
        $this->runCommand($client, 'doctrine:fixtures:load', ['--no-interaction']);
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