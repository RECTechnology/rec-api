<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use HalcashServiceMx;

//Include the class
include("libs/HalcashServiceMx.php");

class HalcashMx{

    public function getHalcashSend($mode){


        return new HalcashServiceMx($mode);

    }

    public function getHalcashPayment($mode){

        return new HalcashServiceMx($mode);

    }

}