<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\HalcashServiceSp;


class HalcashSp{

    public function getHalcashSend($mode){


        return new HalcashServiceSp($mode);

    }

    public function getHalcashPayment($mode){

        return new HalcashServiceSp($mode);

    }

}