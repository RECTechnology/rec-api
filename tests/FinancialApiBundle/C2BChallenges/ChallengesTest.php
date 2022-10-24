<?php

namespace Test\FinancialApiBundle\C2BChallenges;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Challenge;
use Test\FinancialApiBundle\BaseApiTest;

class ChallengesTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_USER_CREDENTIALS);
    }

    function testCreateChallengeFromUserShouldFail(){
        $route = '/user/v3/challenges';

        $start = new \DateTime();
        $finish = new \DateTime('+3 days');
        $data = array(
            'title' => $this->faker->name,
            'description' => $this->faker->text,
            'action' => 'buy',
            'status' => 'draft',
            'threshold' => 3,
            'amount_required' => 0,
            'start_date' => $start->format('Y-m-d\TH:i:sO'),
            'finish_date' => $finish->format('Y-m-d\TH:i:sO'),
            'cover_image' => 'https://fakeimage.es/images/1.jpg'
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );


    }

    function testUpdateChallengeFromUserShouldWork(){
        $route = '/user/v3/challenges/1';

        $data = array(
            'amount_required' => 100*1e8,
        );
        $resp = $this->requestJson('PUT', $route, $data);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testListChallengeFromUserShouldWork(){
        $route = '/user/v3/challenges';

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];

        self::assertEquals(5, $content['data']['total']);
        self::assertArrayHasKey('title', $elements[0]);
        self::assertArrayHasKey('description', $elements[0]);
        self::assertArrayHasKey('action', $elements[0]);
        self::assertArrayHasKey('status', $elements[0]);
        self::assertArrayHasKey('threshold', $elements[0]);
        self::assertArrayHasKey('amount_required', $elements[0]);
        self::assertArrayHasKey('start_date', $elements[0]);
        self::assertArrayHasKey('finish_date', $elements[0]);
        self::assertArrayHasKey('cover_image', $elements[0]);
        self::assertArrayHasKey('statistics', $elements[0]);
    }
    function testListChallengeWithFilterFromUserShouldWork(){
        $route = '/user/v3/challenges?status=open';

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];

        self::assertEquals(3, $content['data']['total']);
        foreach ($elements as $element){
            self::assertEquals(Challenge::STATUS_OPEN, $element['status']);
        }

    }

    function testListChallengeByTypeChallengeFromUserShouldWork(){
        $route = '/user/v3/challenges?type=challenge';

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];

        foreach ($elements as $element){
            self::assertEquals(Challenge::TYPE_CHALLENGE, $element['type']);
        }

    }

    function testListChallengeOrderByStatusFromUserShouldWork(){
        $route = '/user/v3/challenges?sort=status&order=DESC';

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $elements = $content['data']['elements'];

        self::assertEquals(Challenge::STATUS_SCHEDULED, $elements[0]['status']);

    }

}