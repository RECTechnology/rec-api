<?php

namespace App\Tests;

use App\Document\Transaction;
use Doctrine\ODM\MongoDB\DocumentManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

abstract class BaseApiTest extends WebTestCase {

    use TestDataFactory;

    const CRUD_V3_ROUTES = [
        'pos',
        'payment_orders',
        'neighbourhoods',
        'activities',
        'product_kinds',
        'users',
        'accounts',
        'categories',
        'delegated_changes',
        'delegated_change_data',
        'treasure_withdrawals',
        'treasure_withdrawal_validations',
        'access_tokens',
        'clients',
        'cash_in_deposits',
        'user_wallets',
        'limit_counts',
        'limit_definitions',
        'mailings',
        'mailing_deliveries'
    ];

    const HEADERS_JSON = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json'
    ];

    /** @var Generator $faker */
    protected $faker;

    /** @var array $token */
    protected $token;

    protected static $injections = [];

    private $ip = '127.0.0.1';

    protected function setClientIp($ip){
        $this->ip = $ip;
    }

    protected static function inject($service, $mock){
        static::$injections[$service] = $mock;
    }

    protected static function restore($service){
        unset(static::$injections[$service]);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $content
     * @param array $headers
     * @param array $parameters
     * @param array $files
     * @return Response
     */
    protected function request(string $method, string $url, string $content = null, array $headers = [], array $parameters = [], array $files = []) {
        if($this->token) $headers['HTTP_AUTHORIZATION'] = "Bearer {$this->token['access_token']}";
        self::ensureKernelShutdown();
        $client = static::createClient([], ['REMOTE_ADDR' => $this->ip]);
        $client->request($method, $url, $parameters, $files, $headers, $content);
        return $client->getResponse();
    }

    protected static function createClient(array $options = [], array $server = []) {
        self::ensureKernelShutdown();
        $client = parent::createClient($options, $server);
        foreach (static::$injections as $service => $mock) $client->getContainer()->set($service, $mock);
        return $client;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $content
     * @param array $headers
     * @return Response
     */
    protected function requestJson(string $method, string $url, array $content = null, array $headers = []) {
        if($content !== null) $content = json_encode($content);
        $resp = $this->request($method, $url, $content, array_merge($headers, self::HEADERS_JSON));
        if($resp->getStatusCode() != Response::HTTP_NO_CONTENT)
            self::assertJson($resp->getContent());
        return $resp;
    }

    const HTTP_REST_RESPONSE_CODES = [
        'GET' => [Response::HTTP_OK],
        'POST' => [Response::HTTP_CREATED],
        'PUT' => [Response::HTTP_OK],
        'DELETE' => [Response::HTTP_NO_CONTENT],
    ];

    /**
     * @param string $method
     * @param string $url
     * @param array|null $content
     * @param array $headers
     * @param string $expectedStatusCode
     * @return \stdClass|array
     */
    protected function rest(string $method, string $url, array $content = null, array $headers = [], $expectedStatusCode = 'success_http') {
        $resp = $this->requestJson($method, $url, $content, $headers);
        if($expectedStatusCode == 'success_http') {
            self::assertContains(
                $resp->getStatusCode(),
                self::HTTP_REST_RESPONSE_CODES[$method],
                "Path: {$url}, Content: {$resp->getContent()}"
            );
        }
        else {
            self::assertEquals(
                $expectedStatusCode,
                $resp->getStatusCode(),
                "Path: {$url}, Content: {$resp->getContent()}"
            );

        }
        $content = json_decode($resp->getContent());
        if (isset($content->data)) {
            $content = $content->data;
            if (!is_array($content) && property_exists($content, 'elements')) {
                return $content->elements;
            }
            return $content;
        }
        return $content;
    }


    /**
     * @param $credentials
     */
    protected function signIn($credentials){
        $oauthClient = $this->getOAuthClient();
        $content = [
            'client_id' => $oauthClient->getPublicId(),
            'client_secret' => $oauthClient->getSecret(),
            'grant_type' => 'password',
            'username' => $credentials['username'],
            'password' => $credentials['password']
        ];

        $resp = $this->requestJson('POST', '/oauth/v2/token', $content);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "status_code: {$resp->getStatusCode()} content: {$resp->getContent()}"
        );
        self::assertEquals('application/json', $resp->headers->get('Content-Type'));
        $this->token = json_decode($resp->getContent(), true);
    }

    protected function getSignedInUser(){
        return $this->rest('GET', '/user/v1/account');
    }


    protected static function debug($stuff){
        die(print_r($stuff, true));
    }

    protected function signOut(){
        $this->token = null;
    }

    /**
     * @throws \Exception
     */
    protected function clearDatabase(){
        $this->removeDatabase();
        $this->runCommand('doctrine:database:create', ['--if-not-exists']);
        $this->runCommand('doctrine:schema:create');
    }

    protected function clearMongo() {
        self::ensureKernelShutdown();
        /** @var DocumentManager $dm */
        $dm = self::createClient()->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $txs = $dm->getRepository(Transaction::class)->findAll();
        foreach ($txs as $tx) $dm->remove($tx);
        $dm->flush();
    }

    /**
     * @param string $string
     * @return string|string[]|null
     */
    protected function resolveString(string $string){
        preg_match_all('/%([^%]+)%/', $string, $matches);
        if(count($matches) > 1){
            for($i = 0; $i < count($matches[0]); $i++){
                $paramName = $matches[1][$i];
                $match = $matches[0][$i];
                self::ensureKernelShutdown();
                $param = self::createClient()->getContainer()->getParameter($paramName);
                $string = preg_replace("/$match/", $param, $string);
            }
        }
        return $string;
    }

    protected function removeDatabase() {
        $fs = new Filesystem();
        self::ensureKernelShutdown();
        $dbFile = self::createClient()->getKernel()->getCacheDir() . '/rdb/db.sqlite';
        if($fs->exists($dbFile)) $fs->remove($dbFile);
    }

    /**
     * @param string $command
     * @param array $args
     * @return string
     * @throws \Exception
     */
    protected function runCommand(string $command, array $args = []){
        self::ensureKernelShutdown();
        $application = new Application(self::createClient()->getKernel());
        $application->setAutoExit(false);
        $fullCommand = array_merge(['command' => $command], $args);
        $output = new BufferedOutput();
        $application->setCatchExceptions(false);
        $application->run(new ArrayInput($fullCommand), $output);
        return $output->fetch();
    }

    /**
     * @throws \Exception
     */
    protected function loadFixtures(){
        $this->runCommand('doctrine:fixtures:load', ['--no-interaction']);
    }

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->clearDatabase();
        $this->loadFixtures();
        $this->clearMongo();
    }

    protected function tearDown(): void {
        $this->removeDatabase();
        parent::tearDown();
    }

    private function getDebugDir(){
        self::ensureKernelShutdown();
        $cacheDir = self::createClient()->getContainer()->getParameter('kernel.cache_dir');
        $debugDir = $cacheDir . '/debug';
        if(!file_exists($debugDir)) mkdir($debugDir);
        return $debugDir;
    }

    /**
     * @param $filename
     * @param $content
     */
    protected function dump($filename, $content){
        $debugDir = $this->getDebugDir();
        file_put_contents("$debugDir/$filename", $content);
    }

    /**
     * @param $filename
     * @return false|string
     */
    protected function load($filename){
        $debugDir = $this->getDebugDir();
        return file_get_contents("$debugDir/$filename");
    }

    protected function getCryptoCurrency(){
        self::ensureKernelShutdown();
        return self::createClient()->getContainer()->getParameter('crypto_currency');
    }

    protected function getCryptoMethod(){
        return strtolower($this->getCryptoCurrency());
    }

}
