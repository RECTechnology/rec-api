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


    public function testUserRoutesShouldReturn401(){

        $client = static::createClient();

        /** @var RouterInterface $router */
        $router = $client->getKernel()->getContainer()->get('router');

        $routes = $router->getRouteCollection()->all();

        foreach($routes as $route){
            print_r($route);
        }


        $client->request(
            'GET',
            '/user/v1/'
        );
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
    }
}
