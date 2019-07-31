<?php

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseMethod extends AbstractMethod {

    private $container;
    private $default_fixed_fee;
    private $default_variable_fee;

    public function __construct($name, $cname, $type, $currency, $emial_required, $base64Image, $image, ContainerInterface $container, $min_tier, $default_fixed_fee, $default_variable_fee){
        parent::__construct($name, $cname, $type, $currency, $emial_required, $base64Image, $image, $min_tier);
        $this->container = $container;
        $this->default_fixed_fee = $default_fixed_fee;
        $this->default_variable_fee = $default_variable_fee;
    }

    /**
     * @return TransactionContextInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Boolean
     */
    public function checkKYC(Request $request, $type){
        return $request;
    }

    public function getDefaultFixedFee(){
        return $this->default_fixed_fee;
    }

    public function getDefaultVariableFee(){
        return $this->default_variable_fee;
    }
}
