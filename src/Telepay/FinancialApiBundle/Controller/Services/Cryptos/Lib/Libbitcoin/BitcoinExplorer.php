<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/16/15
 * Time: 7:07 PM
 */


namespace Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\Libbitcoin;


use Symfony\Component\HttpKernel\Exception\HttpException;

class BitcoinExplorer {

    private static $BX = "/usr/local/bin/bx";
    private static $RETRIES = 3;

    private function bxCall($func, array $params = array()){
        $format = " -f json";
        if($func == "fetch-height") $format = "";
        $stringParams = join(" ", $params);
        $call = static::$BX." ".$func." ".$stringParams.$format;
        $bxResp = shell_exec(static::$BX." ".$func." ".$stringParams.$format);
        $count = 0;
        while($bxResp == "" && $count < static::$RETRIES){
            $bxResp = shell_exec(static::$BX." ".$func." ".$stringParams.$format);
            $count++;
        }
        if($count >= static::$RETRIES) throw new HttpException(503, "Bitcoin service temporary unavailable (".$call.")");
        return json_decode($bxResp, true);
    }

    public function getBalance($address){
        $resp = $this->bxCall("fetch-balance", array($address));
        return $resp['balance'];
    }

    public function getConfirmations($address, $received = 0){
        $resp1 = $this->bxCall("fetch-history", array($address));
        $resp2 = $this->bxCall("fetch-height");
        //die(print_r($resp1['transfers']['transfer']['received']['height'], true));
        return $resp2-$resp1['transfers']['transfer']['received']['height'];
    }
}