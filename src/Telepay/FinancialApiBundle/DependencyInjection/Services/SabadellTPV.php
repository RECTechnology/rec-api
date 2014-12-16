<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\SabadellService;

class SabadellTPV{

    //This parameters are unique for us. Don't give to the client
    //For Test are 7 , 1 , 1, 1 , 1
    private $testArray =array(
        'url_tpvv'          =>  'https://sis-t.redsys.es:25443/sis/realizarPago',
        'clave'             =>  'qwertyasdf0123456789',
        'name'              =>  'Telepay',
        'code'              =>  '327714929',
        'currency'          =>  '978',
        'transaction_type'  =>  '0',
        'terminal'          =>  '1'
    );

    private $prodArray =array(
        'url_tpvv'          =>  'https://sis.redsys.es/sis/realizarPago',
        'clave'             =>  'xuauoudpjpak78318334',
        'name'              =>  'Telepay',
        'code'              =>  '327714929',
        'currency'          =>  '978',
        'transaction_type'  =>  '0',
        'terminal'          =>  '1'
    );

    public function getSabadellTest($amount,$transaction_id,$description,$url_notification,$url_ok,$url_ko){

        return new SabadellService($amount,$transaction_id,$description,$url_notification,$url_ok,$url_ko,$this->testArray['url_tpvv'],$this->testArray['clave'],$this->testArray['name'],$this->testArray['code'],$this->testArray['currency'],$this->testArray['transaction_type'],$this->testArray['terminal']);

    }

    public function getSabadell($amount,$transaction_id,$description,$url_notification,$url_ok,$url_ko){

        return new SabadellService($amount,$transaction_id,$description,$url_notification,$url_ok,$url_ko,$this->prodArray['url_tpvv'],$this->prodArray['clave'],$this->prodArray['name'],$this->prodArray['code'],$this->prodArray['currency'],$this->prodArray['transaction_type'],$this->prodArray['terminal']);

    }

}