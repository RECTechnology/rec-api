<?php

namespace Test\FinancialApiBundle\Admin\Translations;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class SubresourceTranslationsTest
 * @package Test\FinancialApiBundle\Admin\Translations
 */
class SubresourceTranslationsTest extends BaseApiTest {

    const LANG_PARAMS = [
        'en' => ['name' => 'name-en'],
        'es' => ['name' => 'name-es'],
        'ca' => ['name' => 'name-ca']
    ];

    private $account;
    private $activity;

    /**
     * @brief Given a ADMIN login, associate activity and products to the activity, and after read in specific language
     * and 'all' languages
     * @throws \Exception
     */
    function setUp(): void {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);

        $route = '/admin/v3/activities';
        $lang = 'en';

        // create activity, Lang: en
        $resp = $this->requestJson(
            'POST',
            $route,
            self::LANG_PARAMS[$lang],
            ['HTTP_Content-Language' => $lang]
        );

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $activity = json_decode($resp->getContent())->data;
        self::assertIsNumeric($activity->id);
        $this->activity = $activity;

        // add more languages (self::LANG_PARAMS)
        foreach (self::LANG_PARAMS as $lang => $param){
            $resp = $this->requestJson(
                'PUT',
                $route . '/' . $activity->id,
                $param,
                ['HTTP_Content-Language' => $lang]
            );

            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

        $resp = $this->requestJson('GET', '/admin/v3/accounts');
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: /admin/v3/accounts, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $content = json_decode($resp->getContent());
        self::assertGreaterThan(0, count($content->data->elements));

        $account = $content->data->elements[0];
        $route = '/admin/v3/accounts/' . $account->id . '/activities';

        $resp = $this->requestJson(
            'POST',
            $route,
            ['id' => $activity->id]
        );

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $this->account = $account;
    }

    function testSubresourceShouldBeTranslatedInEveryLanguage() {
        self::assertIsNumeric($this->account->id);

        foreach (self::LANG_PARAMS as $lang => $params) {
            $content = json_decode(
                $this
                    ->requestJson(
                        'GET',
                        '/admin/v3/accounts/' . $this->account->id,
                        null,
                        ['HTTP_Accept-Language' => $lang]
                    )
                    ->getContent()
            );
            // ensure we got the specified account
            self::assertEquals($content->data->id, $this->account->id);

            // ensure has activities and the working activity
            self::assertObjectHasAttribute('activities', $content->data);

            $filtered = array_values(array_filter($content->data->activities, fn($a) => $a->id == $this->activity->id));
            self::assertGreaterThan(0, count($filtered));
            $activity = $filtered[0];

            foreach ($params as $param => $value) {
                self::assertEquals($value, $activity->$param, "req LANG: $lang");
            }
        }

    }
}
