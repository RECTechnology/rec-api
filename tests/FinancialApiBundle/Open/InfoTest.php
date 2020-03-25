<?php

namespace Test\FinancialApiBundle\Open;

use Test\FinancialApiBundle\BaseApiTest;

class InfoTest extends BaseApiTest {

    public function testInfoIsCorrect(){
        $route = "/public/v1/info";
        $info = $this->rest('GET', $route);
        self::assertObjectHasAttribute("name", $info);
        self::assertObjectHasAttribute("license", $info);
        self::assertObjectHasAttribute("description", $info);
        self::assertObjectHasAttribute("version", $info);
    }
}