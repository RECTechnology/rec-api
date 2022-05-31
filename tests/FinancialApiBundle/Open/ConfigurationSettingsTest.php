<?php

namespace Test\FinancialApiBundle\Open;

use App\FinancialApiBundle\Entity\ConfigurationSetting;
use Test\FinancialApiBundle\BaseApiTest;

class ConfigurationSettingsTest extends BaseApiTest {

    public function testListConfigurationSettingsShouldReturnOnlyAppSettings(){
        $route = "/public/v3/configuration_settings";
        $settings = $this->requestJson('GET', $route);

        $content = json_decode($settings->getContent(),true);
        $data = $content['data']['elements'];
        foreach ($data as $setting){
            self::assertEquals(ConfigurationSetting::APP_SCOPE, $setting['scope']);
        }
    }
}