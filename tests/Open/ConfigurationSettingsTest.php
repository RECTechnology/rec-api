<?php

namespace App\Tests\Open;

use App\Entity\ConfigurationSetting;
use App\Tests\BaseApiTest;

class ConfigurationSettingsTest extends BaseApiTest {

    public function testListConfigurationSettingsShouldReturnOnlyAppSettings(){
        $route = "/public/v3/configuration_settings";
        $settings = $this->requestJson('GET', $route);
        self::assertResponseIsSuccessful();

        $content = json_decode($settings->getContent(),true);
        $data = $content['data']['elements'];
        foreach ($data as $setting){
            self::assertEquals(ConfigurationSetting::APP_SCOPE, $setting['scope']);
        }
    }
}