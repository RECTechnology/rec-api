<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/27/14
 * Time: 3:13 PM
 */

namespace App\FinancialApiBundle\DependencyInjection;

use App\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractExchange;

class AppExchangeProvider{

    private $exchangeByCName;

    public function __construct($exchanges){
        $this->exchangeByCName = array();
        foreach($exchanges as $exchange){
            $this->exchangeByCName[$exchange->getCName()] = $exchange;
        }
    }

    public function findAll(){
        $exchanges = array();
        foreach($this->exchangeByCName as $exchange){
            $exchanges []= new AbstractExchange(
                $exchange->getCurrencyIn(),
                $exchange->getCurrencyOut(),
                $exchange->getCname()

            );
        }
        return $exchanges;
    }

    public function findByCName($cname){
        return $this->exchangeByCName[$cname];
    }

    public function findByCNames(array $cnames){
        $exchanges = array();
        foreach($cnames as $cname){
            if(array_key_exists($cname, $this->exchangeByCName))
                $exchanges []= new AbstractExchange(
                    $this->exchangeByCName[$cname]->getCurrencyIn(),
                    $this->exchangeByCName[$cname]->getCurrencyOut(),
                    $this->exchangeByCName[$cname]->getCname()

                );
        }
        return $exchanges;
    }

}
