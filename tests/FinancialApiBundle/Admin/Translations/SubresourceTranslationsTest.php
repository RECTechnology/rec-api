<?php

namespace Test\FinancialApiBundle\Admin\Translations;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

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

        $resp = $this->requestJson(
            'POST',
            $route,
            self::LANG_PARAMS[$lang],
            ['Content-Language' => $lang]
        );

        self::assertEquals(
            201,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
        $activity = json_decode($resp->getContent())->data;
        self::assertIsNumeric($activity->id);

        foreach (self::LANG_PARAMS as $lang => $param){
            $resp = $this->requestJson(
                'PUT',
                $route . '/' . $activity->id,
                $param,
                ['Content-Language' => $lang]
            );

            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

        $content = json_decode($this->requestJson('GET', '/admin/v3/accounts')->getContent());
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


    function testSubresourceShouldBeTranslatedInAllLanguages() {
        self::markAsRisky();

        self::assertIsNumeric($this->account->id);

        foreach (self::LANG_PARAMS as $lang => $params) {
            $content = json_decode(
                $this
                    ->requestJson('GET', '/admin/v3/accounts/' . $this->account->id)
                    ->getContent()
            );
            self::assertEquals($content->data->id, $this->account->id);
            foreach ($params as $param => $value) {
                self::assertEquals($value, $content->data->activities[0]->$param, "req LANG: $lang");
            }
        }

    }
}
