<?php

namespace Test\FinancialApiBundle\Admin\Translations;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;
use Test\FinancialApiBundle\CrudV3WriteTestInterface;

/**
 * Class TranslationsTest
 * @package Test\FinancialApiBundle\Admin\Translations
 */
class TranslationsTest extends BaseApiTest implements CrudV3WriteTestInterface {

    const ROUTES_TO_TEST = [
        'product_kinds' => [
            'es' => ['name' => 'product_kinds es'],
            'en' => ['name' => 'product_kinds en'],
            'ca' => ['name' => 'product_kinds ca']
        ],
        'activities' => [
            'es' => ['name' => 'activities es'],
            'en' => ['name' => 'activities en'],
            'ca' => ['name' => 'activities ca']
        ],
        'neighbourhoods' => [
            'es' => ['name' => 'neighbourhoods es'],
            'en' => ['name' => 'neighbourhoods en'],
            'ca' => ['name' => 'neighbourhoods ca']
        ],
    ];

    const LANGUAGES_TO_TEST = [
        'en', 'es', 'ca'
    ];

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testBothTranslatableAndNoTranslatableEnitiesShouldSuccessToIndex()
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

    function testCreate() {
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

    function testUpdate() {
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
        $lang = 'en';
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
            $object = json_decode($resp->getContent());

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
