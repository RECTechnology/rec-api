<?php

namespace Test\FinancialApiBundle\Admin\B2B;

use App\FinancialApiBundle\Controller\Management\User\DiscourseController;
use App\FinancialApiBundle\DataFixture\AccountFixture;
use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Entity\Group;
use PHPUnit\Util\Json;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class B2BManagementTest
 * @package Test\FinancialApiBundle\Admin\B2B
 */
class B2BManagementTest extends BaseApiTest {

    public function testGrantAccessToDiscourse(): void
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $accountName = AccountFixture::TEST_ACCOUNT_REZERO_1['name'];

        //get account
        $route = '/admin/v3/accounts?name='.$accountName;
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];
        self::assertArrayHasKey('elements', $data);
        self::assertGreaterThan(0, count($data['elements']));
        $account = $data['elements'][0];
        self::assertEquals(Group::ACCESS_STATE_PENDING, $account["rezero_b2b_access"]);

        //grant access to forum
        $route = '/admin/v3/accounts/'.$account['id'];
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_access" => Group::ACCESS_STATE_GRANTED));
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $content = json_decode($resp->getContent(), true);
        self::assertEquals($content['message'],"Rezero Username is not set" );

        //Setting rezero username
        $username = $this->faker->userName;
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_username" => $username));
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        //now we can use mock and grant access again
        $this->useDiscourseMock();
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_access" => Group::ACCESS_STATE_GRANTED));
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        $account = $content['data'];
        self::assertEquals(Group::ACCESS_STATE_GRANTED, $account["rezero_b2b_access"]);
        self::assertArrayHasKey("rezero_b2b_api_key", $account);
        self::assertArrayHasKey("rezero_b2b_user_id", $account);

    }

    public function testGrantAccessBadEmailShouldFail(): void
    {
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
        $accountName = AccountFixture::TEST_ACCOUNT_REZERO_1['name'];

        //get account
        $route = '/admin/v3/accounts?name='.$accountName;
        $resp = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);
        self::assertArrayHasKey('data', $content);
        $data = $content['data'];
        self::assertArrayHasKey('elements', $data);
        self::assertGreaterThan(0, count($data['elements']));
        $account = $data['elements'][0];
        self::assertEquals(Group::ACCESS_STATE_PENDING, $account["rezero_b2b_access"]);

        //grant access to forum
        $route = '/admin/v3/accounts/'.$account['id'];

        //Setting rezero username and bad email
        $username = $this->faker->userName;
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_username" => $username, "email" => $username."@".$username.".com"));
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        //now we can use mock and grant access again
        $this->useBadEmailMock();
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_access" => Group::ACCESS_STATE_GRANTED));
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);

        self::assertEquals("Primary email no es válido.", $content['message']);

        $resp = $this->requestJson('PUT', $route, array("email" => $username."@valid.com"));
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $this->useTooMuchRegistersFromSameIpMock();
        $resp = $this->requestJson('PUT', $route, array("rezero_b2b_access" => Group::ACCESS_STATE_GRANTED));
        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(), true);

        self::assertStringContainsString("No se permiten nuevos registros desde tu dirección IP", $content['message']);

    }

    private function useDiscourseMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = array(
            'success' => true,
            'active' => true,
            'message' => "Tu cuenta está activa y lista para usar",
            'user_id' => 14
        );
        $discMock->method('register')->willReturn($response);
        $discMock->method('generateApiKeys')->willReturn("kjlhsdfljshflsdf");
        $discMock->method('subscribeToNewsCategory')->willReturn(array("success" => "OK"));

        $this->override('net.app.commons.discourse.api_manager', $discMock);

    }

    private function useBadEmailMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->badEmailMockResponse();
        $discMock->method('register')->willReturn($response);

        $this->override('net.app.commons.discourse.api_manager', $discMock);

    }

    private function useTooMuchRegistersFromSameIpMock(){
        $discMock = $this->createMock(DiscourseApiManager::class);
        $response = $this->tooMuchRetriesFromSameIp();
        $discMock->method('register')->willReturn($response);

        $this->override('net.app.commons.discourse.api_manager', $discMock);

    }

    private function badEmailMockResponse(){
        return array (
            'success' => false,
            'message' => 'Primary email no es válido.',
            'errors' =>
                array (
                    'email' =>
                        array (
                            0 => 'no es válido.',
                        ),
                ),
            'values' =>
                array (
                    'name' => 'rascayui4',
                    'username' => 'rascayui4',
                    'email' => 'rascayui4@email_test.com',
                ),
            'is_developer' => false,
        );
    }

    private function tooMuchRetriesFromSameIp(){
        return array (
            'success' => false,
            'message' => ' No se permiten nuevos registros desde tu dirección IP (se alcanzó el límite máximo). Contacta un miembro del staff.',
            'errors' =>
                array (
                    'ip_address' =>
                        array (
                            0 => 'No se permiten nuevos registros desde tu dirección IP (se alcanzó el límite máximo). Contacta un miembro del staff.',
                        ),
                ),
            'values' =>
                array (
                    'name' => 'rascayui4',
                    'username' => 'rascayui4',
                    'email' => 'rascayui4@email.com',
                ),
            'is_developer' => false,
        );
    }

}
