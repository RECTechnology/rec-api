<?php

namespace Telepay\FinancialApiBundle\Tests\Web;

use Telepay\FinancialApiBundle\DependencyInjection\TestClientFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApiWebTestCase extends WebTestCase
{
    protected  static function getTestClient($role){
        return static::createClient(array(), array(
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X-SIGNATURE' => TestClientFactory::get($role)
        ));
    }
}
