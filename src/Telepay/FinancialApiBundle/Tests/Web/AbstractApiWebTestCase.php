<?php

namespace Telepay\FinancialApiBundle\Tests\Web;

use Telepay\FinancialApiBundle\DependencyInjection\SignatureHeaderBuilder;
use Telepay\FinancialApiBundle\DependencyInjection\TestClientFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Tests\TestClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\SecureRandom;

abstract class AbstractApiWebTestCase extends WebTestCase
{
    protected  static function getTestClient($role){
        return static::createClient(array(), array(
            'HTTP_HOST' => 'api.telepayinternational.com',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-SIGNATURE' => TestClientFactory::get($role)
        ));
    }
}
