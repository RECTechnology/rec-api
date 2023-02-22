<?php

namespace App\Tests\Open;

use App\Tests\BaseApiTest;

class StatusTest extends BaseApiTest {

    public function testAllIsOnline(){
        $route = "/public/v1/status";
        $status = $this->rest('GET', $route);
        self::assertEquals(7, $status->system_status);
        self::assertCount(0, $status->exceptions);
    }
}