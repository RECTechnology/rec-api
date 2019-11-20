<?php

namespace Test\FinancialApiBundle\Command;

use Test\FinancialApiBundle\BaseApiTest;

class SynchronizeLemonwayDataTest extends BaseApiTest
{

    function testCommandReturnsContentAndDoesntCrash(){
        $output = $this->runCommand("rec:sync:lemonway");
        self::assertNotEmpty($output);
    }

}
