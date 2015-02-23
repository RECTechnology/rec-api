<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\HalcashServiceMx;


class HalcashMx{

    public function getHalcashSend($mode){


        return new HalcashServiceMx($mode);

    }

    public function getHalcashPayment($mode){

        return new HalcashServiceMx($mode);

    }

}