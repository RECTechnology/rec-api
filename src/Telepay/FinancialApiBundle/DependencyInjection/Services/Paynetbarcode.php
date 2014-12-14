<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\PaynetGetBarcode;
use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\PaynetGetStatus;


class Paynetbarcode{

    public function getPaynetGetBarcode(){

        return new PaynetGetBarcode();

    }

    public function getPaynetGetStatus(){

        return new PaynetGetStatus();

    }

}