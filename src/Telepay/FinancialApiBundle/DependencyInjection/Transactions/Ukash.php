<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\UkashRedirect;

class Ukash{

    private $mode;

    public function getUkash($mode){

        $this->mode=$mode;
        return new UkashRedirect($mode);
    }

}