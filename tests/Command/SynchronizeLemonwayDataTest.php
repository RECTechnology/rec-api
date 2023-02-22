<?php

namespace App\Tests\Command;

use App\Financial\Driver\LemonWayInterface;
use App\Financial\Methods\LemonWayMethod;
use App\Tests\BaseApiTest;

class SynchronizeLemonwayDataTest extends BaseApiTest
{

    function testCommandReturnsContentAndDoesntCrash(){

        $lw = $this->createMock(LemonWayInterface::class);

        $data = new \stdClass();
        $data->wallets = [];
        $lw->method('callService')
            ->with(['serviceName' => 'GetWalletDetailsBatch'])
            ->willReturn($data);
        self::inject('net.app.in.lemonway.v1', $lw);

        $output = $this->runCommand("rec:sync:lemonway");
        self::assertNotEmpty($output);
    }

}
