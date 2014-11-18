<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use HalcashServiceSp;

//Include the class
include("libs/HalcashServiceSp.php");

class HalcashSp{

    public function getHalcashSend($mode){


        return new HalcashServiceSp($mode);

    }

    public function getHalcashPayment($mode){

        return new HalcashServiceSp($mode);

    }

}