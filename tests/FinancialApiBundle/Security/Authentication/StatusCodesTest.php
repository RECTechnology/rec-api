<?php

namespace Test\FinancialApiBundle\Security\Authentication;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Routing\Route;
use Test\FinancialApiBundle\BaseApiTest;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class StatusCodesTest
 * @package Test\FinancialApiBundle\Security\Authentication
 */
class StatusCodesTest extends BaseApiTest {

    public function testSlashShouldReturn404(){
        $client = self::createClient();
        $client->request('GET', '/');
        $response = $client->getResponse();
        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @param Client $client
     * @return Route[]
     */
    private function getAllRoutes(Client $client){
        /** @var RouterInterface $router */
        $router = $client->getKernel()->getContainer()->get('router');
        return $router->getRouteCollection()->all();
    }

    public function testPublicAndNotParametrizedRoutesAreGetAndReturns200(){
        $routes = $this->getAllRoutes(static::createClient());

        foreach($routes as $route){
            $parts = explode("/", $route->getPath());
            if($parts[1] === "public" and ! preg_match("/{[a-z0-9_]+}/", $route->getPath()))
                foreach($route->getMethods() as $method){
                    self::assertEquals("GET", $method, "Route {$route->getPath()} is $method");
                    $response = $this->request($method, $route->getPath());
                    self::assertEquals(
                        200,
                        $response->getStatusCode(),
                        "Problem with  --> {$method} {$route->getPath()} <-- RESP: {$response->getContent()}"
                    );
                }
        }
    }

    public function testNotPublicAndNotParametrizedRoutesReturns401(){
        $this->markTestIncomplete("Routes are still not homogeneous, so this test doesn't make sense yet.");

        $client = $this->request();
        $routes = $this->getAllRoutes();

        foreach($routes as $route){
            $parts = explode("/", $route->getPath());
            if($parts[1] !== "public" and ! preg_match("/{[a-z0-9_]+}/", $route->getPath()))
                foreach($route->getMethods() as $method){
                    $client->request($method, $route->getPath());
                    $response = $client->getResponse();
                    self::assertEquals(
                        401,
                        $response->getStatusCode(),
                        "Problem with --> {$method} {$route->getPath()} <--"
                    );
                }
        }
    }
}
