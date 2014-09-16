<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use PaynetGetBarcode;
use PaynetGetStatus;

//Include the class
include("libs/PaynetGetBarcode.php");
include("libs/PaynetGetStatus.php");

class Paynetbarcode{

    public function getPaynetGetBarcode(){

        return new PaynetGetBarcode();

    }

    public function getPaynetGetStatus(){

        return new PaynetGetStatus();

    }

}