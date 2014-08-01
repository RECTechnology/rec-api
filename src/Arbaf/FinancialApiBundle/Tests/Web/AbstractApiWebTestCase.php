<?php

namespace Arbaf\FinancialApiBundle\Tests\Web;

use Arbaf\FinancialApiBundle\DependencyInjection\SignatureHeaderBuilder;
use Arbaf\FinancialApiBundle\DependencyInjection\TestClientFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Tests\TestClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\SecureRandom;

abstract class AbstractApiWebTestCase extends WebTestCase
{
    protected  static function getTestClient($role){
        return static::createClient(array(), array(
            'HTTP_HOST' => 'api.arbafinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-SIGNATURE' => TestClientFactory::get($role)
        ));
    }
}
