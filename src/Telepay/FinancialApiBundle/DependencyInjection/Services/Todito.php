<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use ToditoCash;

//Include the class
include("libs/ToditoCash.php");

class Todito{

    private $contract_id=2801;
    private $branch_id='test';

    public function getToditoCash(){

        return new ToditoCash($this->contract_id,$this->branch_id);

    }

}