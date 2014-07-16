<?php

namespace Arbaf\FinancialApiBundle\Tests\Security;

use Arbaf\FinancialApiBundle\Tests\Web\AbstractApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthenticationTest
 * @package Arbaf\FinancialApiBundle\Tests\Controller
 *
 * This Test should test all routes with all types of basic roles (API_SUPER_ADMIN, API_ADMIN, API_USER)
 */
class AuthenticationTest extends AbstractApiWebTestCase
{

    private static $ROLES_PATHS_PERMS = array(
        array(
            'name' => 'ROLE_API_SUPER_ADMIN',
            'tests' => array(
                array('path' => '/admin/clients','code' => Response::HTTP_OK),
                array('path' => '/admin/users','code' => Response::HTTP_OK),
                array('path' => '/admin/groups','code' => Response::HTTP_OK),
                array('path' => '/services/test','code' => Response::HTTP_FORBIDDEN),
            )
        ),
        array(
            'name' => 'ROLE_API_ADMIN',
            'tests' => array(
                array('path' => '/admin/clients','code' => Response::HTTP_FORBIDDEN),
                array('path' => '/admin/users','code' => Response::HTTP_OK),
                array('path' => '/manage/groups','code' => Response::HTTP_FORBIDDEN),
                array('path' => '/services/test','code' => Response::HTTP_FORBIDDEN),
            )
        ),
        array(
            'name' => 'ROLE_API_SERVICES_TEST',
            'tests' => array(
                array('path' => '/manage/clients','code' => Response::HTTP_FORBIDDEN),
                array('path' => '/manage/users','code' => Response::HTTP_FORBIDDEN),
                array('path' => '/manage/groups','code' => Response::HTTP_FORBIDDEN),
                array('path' => '/services/test','code' => Response::HTTP_OK),
                array('path' => '/services/nonexistent','code' => Response::HTTP_NOT_FOUND),
            )
        ),
    );

    public function testGetCodesAreOk() {

        foreach(static::$ROLES_PATHS_PERMS as $perm){
            foreach($perm['tests'] as $test){
                $client = static::getTestClient($perm['name']);
                $client->request('GET', $test['path']);

                $this->assertEquals(
                    $test['code'],
                    $client->getResponse()->getStatusCode(),
                    "Testing: ".$perm['name']." -> ".$test['path']
                );
           }
        }
    }
}
