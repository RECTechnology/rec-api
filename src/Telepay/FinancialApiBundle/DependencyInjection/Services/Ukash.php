<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use UkashRedirect;

//Include the class
include("libs/UkashRedirect.php");

class Ukash{

    private $mode;

    public function getUkash($mode){

        $this->mode=$mode;
        return new UkashRedirect($mode);
    }

}