<?php

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseExchange extends AbstractExchange {

    public function __construct($currency_in, $currency_out, $name){
        parent::__construct($currency_in, $currency_out, $name);
    }

}
