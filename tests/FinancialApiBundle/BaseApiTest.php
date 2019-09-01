<?php

namespace Test\FinancialApiBundle;

use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\Client as OAuthClient;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Faker\Factory;
use Faker\Generator;
use FOS\OAuthServerBundle\Controller\TokenController;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseApiTest extends WebTestCase {

    /** @var Generator $faker */
    protected $faker;

    /** @var array $token */
    protected $token;

    /** @var TestDataFactory $testFactory */
    protected $testFactory;

    /**
     * @param string $method
     * @param string $url
     * @param array|null $content
     * @return Response
     */
    protected function request(string $method, string $url, array $content = null){
        $client = static::createClient();
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ];
        if($this->token) $headers['HTTP_AUTHORIZATION'] = "Bearer {$this->token['access_token']}";

        if($content !== null) $content = json_encode($content);
        $client->request($method, $url, [], [], $headers, $content);
        $resp = $client->getResponse();
        self::assertJson($resp->getContent());
        return $resp;
    }

    /**
     * @param $credentials
     */
    protected function logIn($credentials){
        $oauthClient = $this->testFactory->getOAuthClient();
        $content = [
            'client_id' => $oauthClient->getPublicId(),
            'client_secret' => $oauthClient->getSecret(),
            'grant_type' => 'password',
            'username' => $credentials['username'],
            'password' => $credentials['password']
        ];

        $resp = $this->request('POST', '/oauth/v2/token', $content);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
        self::assertEquals('application/json', $resp->headers->get('Content-Type'));
        $this->token = json_decode($resp->getContent(), true);
    }


    protected static function debug($stuff){
        die(print_r($stuff, true));
    }

    protected function logOut(){
        $this->token = null;
    }

    /**
     * @param Client $client
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
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $client = static::createClient();
        $this->testFactory = new TestDataFactory($client);
        $this->clearDatabase($client);
        $this->loadFixtures($client);
    }
}
