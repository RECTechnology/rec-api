<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\UkashBarcode;

class Ukashgenerate{

    public function getUkashOnline($mode){

        return new UkashBarcode($mode);

    }


}