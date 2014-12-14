<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\PademobileRedirect;


class Pademobile{

    private $mode;

    public function getPademobile($mode){

        $this->mode=$mode;
        return new PademobileRedirect($mode);
    }

}