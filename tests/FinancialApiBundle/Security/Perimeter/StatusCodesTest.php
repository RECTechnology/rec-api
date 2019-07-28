<?php

namespace Test\FinancialApiBundle\Security\Perimeter;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Routing\Route;
use Test\FinancialApiBundle\BaseApiTest;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class StatusCodesTest
 * @package Test\FinancialApiBundle\Security\Perimeter
 */
class StatusCodesTest extends BaseApiTest {

    public function testSlashShouldReturn404(){
        $client = $this->getApiClient();
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

    public function testMapReturns200(){
        $client = $this->getApiClient();

        $client->request('GET', '/public/map/v1/list');
        $response = $client->getResponse();
        self::assertEquals(200,
            $response->getStatusCode(),
            "Problem with  --> GET /public/map/v1/list <-- RESP: {$response->getContent()}"
        );
    }

    public function testPublicAndNotParametrizedRoutesAreGetAndReturns200(){
        $client = $this->getApiClient();
        $routes = $this->getAllRoutes($client);

        foreach($routes as $route){
            $parts = explode("/", $route->getPath());
            if($parts[1] === "public" and ! preg_match("/{[a-z0-9_]+}/", $route->getPath()))
                foreach($route->getMethods() as $method){
                    self::assertEquals("GET", $method, "Route {$route->getPath()} is $method");
                    $client->request($method, $route->getPath());
                    $response = $client->getResponse();
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

        $client = $this->getApiClient();
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
