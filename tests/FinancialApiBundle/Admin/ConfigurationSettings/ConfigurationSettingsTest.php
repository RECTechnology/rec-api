<?php

namespace Test\FinancialApiBundle\Admin\ConfigurationSettings;

use App\FinancialApiBundle\DataFixture\UserFixture;
use Test\FinancialApiBundle\BaseApiTest;

class ConfigurationSettingsTest extends BaseApiTest
{

    function setUp(): void
    {
        parent::setUp();
        $this->signIn(UserFixture::TEST_ADMIN_CREDENTIALS);
    }

    function testListConfigurationSettingsShouldReturnOnlyPurchasedPackages(){
        $route = '/admin/v3/configuration_settings';

        $response = $this->requestJson('GET', $route);
        self::assertEquals(
            200,
            $response->getStatusCode(),
            "route: $route, status_code: {$response->getStatusCode()}, content: {$response->getContent()}"
        );
        $content = json_decode($response->getContent(),true);
        $elements = $content['data']['elements'];
        foreach ($elements as $element){
            self::assertEquals(true, $element['package']['purchased']);
        }

    }

}