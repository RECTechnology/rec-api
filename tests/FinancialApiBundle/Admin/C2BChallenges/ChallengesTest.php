<?php

namespace Test\FinancialApiBundle\Admin\C2BChallenges;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\Challenge;
use Test\FinancialApiBundle\BaseApiTest;

class ChallengesTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testCreateChallengeFromSuperShouldWork(){
        $route = '/admin/v3/challenges';

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
            'cover_image' => 'https://fakeimage.es/images/1.jpg',
            'type' => Challenge::TYPE_CHALLENGE
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testCreateChallengeWithBadgesFromSuperShouldWork(){
        $route = '/admin/v3/challenges';

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
            'cover_image' => 'https://fakeimage.es/images/1.jpg',
            'type' => Challenge::TYPE_CHALLENGE
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $response = json_decode($resp->getContent(),true);

        $route = "/admin/v3/challenges/{$response['data']['id']}/badges";
        $data = ['id' => 1];
        $resp = $this->requestJson('POST', $route, $data);
        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $response = json_decode($resp->getContent(),true);
        $data = $response['data'];
        self::assertArrayHasKey('badges', $data);
        self::assertEquals(1,$data['badges'][0]['id']);

    }

    function testCreateChallengeWithoutStatusFromSuperShouldWork(){
        $route = '/admin/v3/challenges';

        $start = new \DateTime();
        $finish = new \DateTime('+3 days');
        $data = array(
            'title' => $this->faker->name,
            'description' => $this->faker->text,
            'action' => 'buy',
            'threshold' => 3,
            'amount_required' => 0,
            'start_date' => $start->format('Y-m-d\TH:i:sO'),
            'finish_date' => $finish->format('Y-m-d\TH:i:sO'),
            'cover_image' => 'https://fakeimage.es/images/1.jpg',
            'type' => Challenge::TYPE_CHALLENGE
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        $data = $content['data'];
        self::assertEquals(Challenge::STATUS_SCHEDULED, $data['status']);
    }

    function testCreateChallengeWithWrongDatesFromSuperShouldFail(){
        $route = '/admin/v3/challenges';

        $start = new \DateTime();
        $finish = new \DateTime('+3 days');
        $data = array(
            'title' => $this->faker->name,
            'description' => $this->faker->text,
            'action' => 'buy',
            'status' => 'draft',
            'threshold' => 3,
            'amount_required' => 0,
            'start_date' => $finish->format('Y-m-d\TH:i:sO'),
            'finish_date' => $start->format('Y-m-d\TH:i:sO'),
            'cover_image' => 'https://fakeimage.es/images/1.jpg'
        );
        $resp = $this->requestJson('POST', $route, $data);

        self::assertEquals(
            400,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        self::assertEquals('Finish date must be greater than Start Date', $content['errors'][0]['message']);
    }

    function testGetChallengesFromSuperShouldWork(){
        $route = '/admin/v3/challenges';

        $resp = $this->requestJson('GET', $route);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent(),true);
        self::assertEquals(6, $content['data']['total']);
    }

    function testUpdateChallengeFromSuperShouldWork(){
        $route = '/admin/v3/challenges/1';

        $data = array(
            'amount_required' => 100*1e8,
        );
        $resp = $this->requestJson('PUT', $route, $data);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $content = json_decode($resp->getContent());

        self::assertEquals(100*1e8, $content->data->amount_required);
    }

    function testUpdateRewardChallengeFromSuperShouldAddChallengeToReward(){
        $route = '/admin/v3/challenges/1';

        $data = array(
            'token_reward_id' => 5,
        );
        $resp = $this->requestJson('PUT', $route, $data);

        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = '/admin/v3/token_rewards/3';
        $resp = $this->requestJson('GET', $route);
        $content = json_decode($resp->getContent(), true);

        self::assertEquals(6, $content['data']['challenge']['id']);
    }

    function testUpdateChallengeAfterStartedFromSuperShouldFail(){
        //challenge 2 is open , check fixtures if fails
        $route = '/admin/v3/challenges/2';

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

    function testDeleteChallengeFromSuperShouldWork(){

        $route = '/admin/v3/challenges/1';

        $resp = $this->requestJson('DELETE', $route);

        self::assertEquals(
            204,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testDeleteChallengeOpenFromSuperShouldFail(){
        //challenge 2 is open , check fixtures if fails
        $route = '/admin/v3/challenges/2';

        $resp = $this->requestJson('DELETE', $route);

        self::assertEquals(
            403,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

    }

    function testCommandStartCloseChallengesShouldWork(){
        $this->markTestIncomplete('Falata recorrer la respuesta y comprobar que se cierra o abre bien todo');
        $resp = $this->runCommand('rec:challenges:manage');
    }

}