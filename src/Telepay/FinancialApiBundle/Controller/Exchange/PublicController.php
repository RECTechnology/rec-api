<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 2:10 AM
 */


namespace Telepay\FinancialApiBundle\Controller\Exchange;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Financial\Currency;
use FOS\RestBundle\Controller\Annotations as Rest;

class PublicController extends RestApiController{

    /**
     * @Rest\View
     */
    public function ticker(Request $request, $currency){
        $lowCurrency = strtolower($currency);

        return $this->restV2(200, "ok", "Ticker", array(
            Currency::$BTC.'x'.Currency::$EUR => $this->get('net.telepay.exchange.btcx'.$lowCurrency)->getPrice(),
            Currency::$FAC.'x'.Currency::$EUR => $this->get('net.telepay.exchange.facx'.$lowCurrency)->getPrice(),
        ));
    }


    /**
     * @Rest\View
     */
    public function currencies(Request $request){
        return $this->restV2(200, "ok", "Currency information", array(
            Currency::$EUR,
            Currency::$BTC,
            Currency::$FAC,
            Currency::$MXN,
            Currency::$PLN
        ));
    }
}