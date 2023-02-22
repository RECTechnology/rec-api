<?php


namespace App\Financial\Driver;


interface LemonWayInterface {
    function getUserIp();
    function callService($serviceName, $parameters);
}