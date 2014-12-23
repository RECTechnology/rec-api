<?php

namespace Telepay\FinancialApiBundle\Tests\Security;

use Telepay\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthenticationTest
 * @package Telepay\FinancialApiBundle\Tests\Controller
 *
 * This Test should test all routes with all types of basic roles (ROLE_SUPERADMIN, ROLE_ADMIN, ROLE_USER)
 */
class AuthorizationTest extends AbstractApiWebTestCase
{

    private static $ROLES_PATHS_PERMS = array(
        array(
            'name' => 'user_no_services',
            'tests' => array(
                array(
                    'method' => 'GET',
                    'path' => '/services/v1/sample',
                    'code' => Response::HTTP_FORBIDDEN
                ),
                array(
                    'method' => 'POST',
                    'path' => '/services/v1/pademobile/redirect/request',
                    'code' => Response::HTTP_FORBIDDEN
                )
            )
        )
    );

    public function testGetCodesAreOk() {

        foreach(static::$ROLES_PATHS_PERMS as $perm){
            foreach($perm['tests'] as $test){
                $client = static::getTestClient($perm['name']);
                $client->request($test['method'], $test['path']);

                $this->assertEquals(
                    $test['code'],
                    $client->getResponse()->getStatusCode(),
                    "Testing: ".$perm['name']." -> ".$test['path']."\n<<<\n".$client->getResponse()."\n>>>"
                );
           }
        }
    }

}
