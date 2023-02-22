<?php

namespace App\Tests\Open;

use App\Tests\BaseApiTest;

class MetadataTest extends BaseApiTest {

    public function testMetadataIsAvailableWithoutToken(){
        $route = "/public/token/metadata/smart_id/3";
        $response = $this->requestJson('GET', $route);
        self::assertEquals(
            404,
            $response->getStatusCode(),
            "status_code: {$response->getStatusCode()} content: {$response->getContent()}"
        );
    }
}