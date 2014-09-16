<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use PaynetService;

//Include the class
include("libs/PaynetService.php");

class Paynetpay{

    //This parameters are unique for us. Don't give to the client
    //For Test are 7 , 1 , 1, 1 , 1
    private $testArray =array(
        'group_id'  =>  7,
        'chain_id'  =>  1,
        'shop_id'   =>  1,
        'pos_id'    =>  1,
        'cashier_id'=>  1
    );

    //Para producción no los tenemos--de momento he puesto los mismos pero habrá que cambiarlos
    private $prodArray =array(
        'group_id'  =>  7,
        'chain_id'  =>  1,
        'shop_id'   =>  1,
        'pos_id'    =>  1,
        'cashier_id'=>  1
    );

    public function getPaynetPayTest(){

        return new PaynetService($this->testArray['group_id'],$this->testArray['chain_id'],$this->testArray['shop_id'],$this->testArray['pos_id'],$this->testArray['cashier_id']);

    }

    public function getPaynetPay(){

        return new PaynetService($this->prodArray['group_id'],$this->prodArray['chain_id'],$this->prodArray['shop_id'],$this->prodArray['pos_id'],$this->prodArray['cashier_id']);

    }

}