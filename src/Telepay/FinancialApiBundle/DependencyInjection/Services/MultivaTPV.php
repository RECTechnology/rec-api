<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use MultivaService;

//Include the class
include("libs/MultivaService.php");

class MultivaTPV{

    //This parameters are unique for us. Don't give to the client
    //For Test are 7 , 1 , 1, 1 , 1
    private $testArray =array(
        'comcurrency'   =>  '484',
        'comaddress'    =>  'PROSA',
        'commerchant'   =>  '7531853',
        'comstore'      =>  '1234',
        'comterm'       =>  '001',
    );

    //Para producción no los tenemos--de momento he puesto los mismos pero habrá que cambiarlos
    private $prodArray =array(
        'comcurrency'   =>  '484',
        'comaddress'    =>  'PROSA',
        'commerchant'   =>  '7531853',
        'comstore'      =>  '1234',
        'comterm'       =>  '001',
    );

    public function getMultivaTest($amount,$transaction_id,$url_notification){

        return new MultivaService($amount,$this->testArray['comcurrency'],$this->testArray['comaddress'],$transaction_id,$this->testArray['commerchant'],$this->testArray['comstore'],$this->testArray['comterm'],$url_notification);

    }

    public function getMultiva($amount,$transaction_id,$url_notification){

        return new MultivaService($amount,$this->prodArray['comcurrency'],$this->prodArray['comaddress'],$transaction_id,$this->prodArray['commerchant'],$this->prodArray['comstore'],$this->prodArray['comterm'],$url_notification);

    }

}