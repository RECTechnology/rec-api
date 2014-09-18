<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use PademobileRedirect;

//Include the class
include("libs/PademobileRedirect.php");

class Pademobile{

    private $mode;

    public function getPademobile($mode){

        $this->mode=$mode;
        return new PademobileRedirect($mode);
    }

}