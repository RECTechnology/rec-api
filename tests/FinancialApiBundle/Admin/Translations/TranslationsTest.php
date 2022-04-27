<?php

namespace Test\FinancialApiBundle\Admin\Translations;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

/**
 * Class TranslationsTest
 * @package Test\FinancialApiBundle\Admin\Translations
 */
class TranslationsTest extends BaseApiTest {

    const ROUTES_TO_TEST = [
        'product_kinds' => [
            'es' => ['name' => 'product_kinds es'],
            'en' => ['name' => 'product_kinds en'],
            'ca' => ['name' => 'product_kinds ca']
        ],
        // test testFallback falis with fixture data
//        'activities' => [
//            'en' => ['name' => 'activities en'],
//            'es' => ['name' => 'activities es'],
//            'ca' => ['name' => 'activities ca']
//        ],
    ];

    const LANGUAGES_TO_TEST = [
        'en', 'es', 'ca'
    ];

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testBothTranslatableAndNoTranslatableEntitiesShouldSuccessToIndex()
    {
        $route = '/admin/v3/accounts';
        $resp = $this->requestJson('GET', $route, null, ['Accept-Language' => 'all']);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );

        $route = '/admin/v3/product_kinds';
        $resp = $this->requestJson('GET', $route, null, ['Accept-Language' => 'all']);
        self::assertEquals(
            200,
            $resp->getStatusCode(),
            "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
        );
    }

    function testCreateInEveryLanguageIndividually() {
        foreach (self::LANGUAGES_TO_TEST as $lang) {
            foreach (self::ROUTES_TO_TEST as $name => $params) {
                $route = '/admin/v3/' . $name;
                $resp = $this->requestJson(
                    'POST',
                    $route,
                    $params[$lang],
                    ['HTTP_Content-Language' => $lang]
                );
                self::assertEquals(
                    201,
                    $resp->getStatusCode(),
                    "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
                );
            }
        }
    }

    function testCreateInEveryLanguageAtSameTime() {

        foreach (self::ROUTES_TO_TEST as $name => $params) {
            $createParams = [];
            foreach (self::LANGUAGES_TO_TEST as $lang) {
                if($lang == 'en') $createParams ['name'] = $params[$lang]['name'];
                else $createParams ['name_' . $lang] = $params[$lang]['name'];
            }
            $route = '/admin/v3/' . $name;
            $resp = $this->requestJson(
                'POST',
                $route,
                $createParams,
                ['HTTP_Content-Language' => 'en']
            );
            self::assertEquals(
                201,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
        }

    }

    function testUpdate() {
        $this->markTestIncomplete("not working yet :(2. Reviewed");
        foreach (self::ROUTES_TO_TEST as $name => $params) {
            $route = '/admin/v3/' . $name;
            $lang = self::LANGUAGES_TO_TEST[0];
            $resp = $this->requestJson(
                'POST',
                $route,
                $params[$lang],
                ['HTTP_Content-Language' => $lang]
            );
            self::assertEquals(
                201,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
            $content = json_decode($resp->getContent());
            // TEST CREATE AND SHOW
            foreach (self::LANGUAGES_TO_TEST as $lang) {
                $route = '/admin/v3/' . $name . '/' . $content->data->id;
                $resp = $this->requestJson(
                    'PUT',
                    $route,
                    $params[$lang],
                    ['HTTP_Content-Language' => $lang]
                );
                self::assertEquals(
                    200,
                    $resp->getStatusCode(),
                    "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
                );
                $updateContent = json_decode($resp->getContent());

                self::assertEquals(
                    $params[$lang],
                    array_intersect_assoc($params[$lang], (array) $updateContent->data),
                    "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
                );

                $resp = $this->requestJson(
                    'GET',
                    $route,
                    null,
                    ['HTTP_Accept-Language' => $lang]
                );
                self::assertEquals(
                    $params[$lang],
                    array_intersect_assoc($params[$lang], (array) $updateContent->data),
                    "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
                );
            }

            // TEST INDEX
            foreach (self::LANGUAGES_TO_TEST as $lang) {
                $route = '/admin/v3/' . $name;
                $resp = $this->requestJson(
                    'GET',
                    $route,
                    null,
                    ['HTTP_Accept-Language' => $lang]
                );
                $indexContent = json_decode($resp->getContent())->data->elements;
                foreach ($indexContent as $element){
                    self::assertEquals(
                        $params[$lang],
                        array_intersect_assoc($params[$lang], (array) $element),
                        "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
                    );
                }
            }

            $route = '/admin/v3/' . $name;
            $resp = $this->requestJson(
                'GET',
                $route,
                null,
                ['HTTP_Accept-Language' => 'all']
            );
            self::assertEquals(
                200,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
            $content = json_decode($resp->getContent(), true);
            foreach($content['data']['elements'] as $element) {
                self::assertArrayHasKey('translations', $element);
                foreach($element['translations'] as $lang => $translation) {
                    self::assertEquals(
                        $params[$lang],
                        array_intersect_assoc($params[$lang], $translation),
                        "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
                    );
                }
            }
        }
    }

    function testFallback(){
        foreach (self::ROUTES_TO_TEST as $name => $params) {
            $lang = 'en';
            # Creating a object with lang=en
            $route = '/admin/v3/' . $name;
            $resp = $this->requestJson(
                'POST',
                $route,
                $params[$lang],
                ['HTTP_Content-Language' => $lang]
            );
            self::assertEquals(
                201,
                $resp->getStatusCode(),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );
            $object = json_decode($resp->getContent());

            # fetching the object with lang=ca
            $resp = $this->requestJson(
                'GET',
                $route . '/' . $object->data->id,
                null,
                ['HTTP_Accept-Language' => 'ca']
            );
            $updateContent = json_decode($resp->getContent());

            self::assertEquals(
                $params['en'],
                array_intersect_assoc($params['en'], (array) $updateContent->data),
                "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
            );

            $resp = $this->requestJson(
                'GET',
                $route,
                null,
                ['HTTP_Accept-Language' => 'ca']
            );
            $content = json_decode($resp->getContent());

            foreach ($content->data->elements as $element){

                self::assertEquals(
                    $params['en'],
                    array_intersect_assoc($params['en'], (array) $element),
                    "route: $route, status_code: {$resp->getStatusCode()}, content: {$resp->getContent()}"
                );
            }

        }


    }


    function testDelete()
    {
        // TODO: Implement testDelete() method.
    }
}
