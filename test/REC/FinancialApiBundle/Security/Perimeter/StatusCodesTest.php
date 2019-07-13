<?php

namespace REC\FinancialApiBundle\Test\Security\Perimeter;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class StatusCodesTest
 * @package REC\FinancialApiBundle\Test\Security\Perimeter
 */
class StatusCodesTest extends WebTestCase {

    public function testSlashShouldReturn404(){
        $client = static::createClient();
        $client->request(
            'GET',
            '/'
        );
        $response = $client->getResponse();
        self::assertEquals(404, $response->getStatusCode());
    }

    private function getAllRoutes(){
        $client = static::createClient();
        /** @var RouterInterface $router */
        $router = $client->getKernel()->getContainer()->get('router');
        return $router->getRouteCollection()->all();
    }

    public function testPublicAndNotParametrizedRoutesAreGetAndReturns200(){
        $client = static::createClient();
        $routes = $this->getAllRoutes();

        foreach($routes as $route){
            $parts = explode("/", $route->getPath());
            if($parts[1] === "public" and ! preg_match("/{[a-z0-9_]+}/", $route->getPath()))
                foreach($route->getMethods() as $method){
                    self::assertEquals("GET", $method);
                    $client->request($method, $route->getPath());
                    $response = $client->getResponse();
                    self::assertEquals(
                        200,
                        $response->getStatusCode(),
                        "Problem with  --> {$method} {$route->getPath()} <--"
                    );
                }
        }
    }

    public function testNotPublicAndNotParametrizedRoutesReturns401(){

        $client = static::createClient();
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
                        "Problem with  --> {$method} {$route->getPath()} <--"
                    );
                }
        }
    }
}
