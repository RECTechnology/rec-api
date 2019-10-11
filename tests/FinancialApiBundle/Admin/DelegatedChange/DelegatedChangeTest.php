<?php

namespace Test\FinancialApiBundle\Admin\DelegatedChange;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class ReportClientsAndProvidersTest
 * @package Test\FinancialApiBundle\Admin\DelegatedChange
 */
class DelegatedChangeTest extends BaseApiTest implements CrudV3WriteTestInterface {

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    private function createEmptyDelegatedChange(){
        $tomorrow = new \DateTime("tomorrow");
        $route = '/admin/v3/delegated_changes';
        $resp = $this->requestJson(
            'POST',
            $route,
            ['scheduled_at' => $tomorrow->format('c')]
        );
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        return json_decode($resp->getContent());
    }

    function testCreate()
    {
        $dcContent = $this->createEmptyDelegatedChange();
        self::assertGreaterThan(0, $dcContent->data->id);

        $resp = $this->requestJson('GET', '/admin/v3/accounts?type=PRIVATE');
        $users = json_decode($resp->getContent())->data->elements;
        self::assertGreaterThan(0, count($users));
        $resp = $this->requestJson('GET', '/admin/v3/accounts?type=COMPANY&tier=2');
        $exchangers = json_decode($resp->getContent())->data->elements;
        self::assertGreaterThan(0, count($exchangers));
        $exchanger = $exchangers[0];
        foreach ($users as $user){
            $resp = $this->requestJson(
                'POST',
                '/admin/v3/delegated_change_data',
                [
                    'account_id' => $user->id,
                    'exchanger_id' => $exchanger->id,
                    'delegated_change_id' => $dcContent->data->id,
                    'amount' => 200
                ]
            );
            self::assertEquals(201, $resp->getStatusCode(), $resp->getContent());

            $resp = $this->requestJson(
                'GET',
                '/admin/v3/delegated_change_data?delegated_change_id=' . $dcContent->data->id
            );
            self::assertEquals(200, $resp->getStatusCode(), $resp->getContent());

            $resp = $this->requestJson(
                'GET',
                '/admin/v3/delegated_change_data?delegate_change_id=' . $dcContent->data->id
            );
            self::assertEquals(400, $resp->getStatusCode(), $resp->getContent());
        }

    }

    function testUpdate()
    {
        $nextWeek = new \DateTime("next week");
        $content = $this->createEmptyDelegatedChange();
        $route = '/admin/v3/delegated_changes/' . $content->data->id;
        $resp = $this->requestJson(
            'PUT',
            $route,
            ['scheduled_at' => $nextWeek->format('c')]
        );
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $contentChanged = json_decode($resp->getContent());
        self::assertEquals($content->data->id, $contentChanged->data->id);
    }

    function testDelete()
    {
        $content = $this->createEmptyDelegatedChange();
        $route = '/admin/v3/delegated_changes/' . $content->data->id;
        $resp = $this->requestJson('DELETE', $route);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }
}