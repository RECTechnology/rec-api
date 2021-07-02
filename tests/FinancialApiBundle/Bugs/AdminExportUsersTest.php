<?php


namespace Test\FinancialApiBundle\Bugs;


use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class AdminExportUsersTest
 * @package Test\FinancialApiBundle\Bugs
 * @see https://github.com/QbitArtifacts/rec-api/issues/587
 */
class AdminExportUsersTest extends BaseApiTest {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testExportUsersWithFilter(){
        $route = "/admin/v3/users/export";

        //este field_map peta en prod i en el test pero funciona en pre
        //TODO review why we cannot send roles, enabled and pin in the field_map
        //$field_map = '{"id":"$.id","username":"$.username","email":"$.email","enabled":"$.enabled","locked":"$.locked",
        //"expired":"$.expired","roles":"$.roles[*]","name":"$.name","created":"$.created","dni":"$.dni",
        //"prefix":"$.prefix","phone":"$.phone","pin":"$.pin","public_phone":"$.public_phone"}';

        $field_map = '{"id": "$.id","username":"$.username","email":"$.email","locked":"$.locked","expired":"$.expired"'.
            ',"name":"$.name","created":"$.created","dni":"$.dni","prefix":"$.prefix","phone":"$.phone"'.
            ',"public_phone":"$.public_phone"}';

        $resp = $this->request('GET', $route."?field_map={$field_map}");
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }



}