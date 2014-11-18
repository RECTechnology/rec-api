<?php

namespace Telepay\FinancialApiBundle\Tests\Security;

use Telepay\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthenticationTest
 * @package Telepay\FinancialApiBundle\Tests\Controller
 *
 * This Test should test all routes with all types of basic roles (API_SUPER_ADMIN, API_ADMIN, API_USER)
 */
class AuthenticationTest extends AbstractApiWebTestCase
{

    private static $ROLES_PATHS_PERMS = array(
        array(
            'name' => 'ROLE_USER',
            'tests' => array(
                array('path' => '/v1/services/test','code' => Response::HTTP_OK)
            )
        )
    );

    public function testGetCodesAreOk() {

        foreach(static::$ROLES_PATHS_PERMS as $perm){
            foreach($perm['tests'] as $test){
                $client = static::getTestClient($perm['name']);
                $client->request('GET', $test['path']);

                $this->assertEquals(
                    $test['code'],
                    $client->getResponse()->getStatusCode(),
                    "Testing: ".$perm['name']." -> ".$test['path']."\n<<<\n".$client->getResponse()."\n>>>"
                );
           }
        }
    }

}
