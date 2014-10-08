<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use UkashBarcode;

//Include the class
include("libs/UkashBarcode.php");

class Ukashgenerate{

    public function getUkashOnline($mode){

        return new UkashBarcode($mode);

    }


}