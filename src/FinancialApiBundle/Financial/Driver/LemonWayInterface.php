<?php


namespace App\FinancialApiBundle\Financial\Driver;


interface LemonWayInterface {
    function getUserIp();
    function callService($serviceName, $parameters);
}