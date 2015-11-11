<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 2:10 AM
 */


namespace Telepay\FinancialApiBundle\Controller\Exchange;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Financial\Currency;
use FOS\RestBundle\Controller\Annotations as Rest;

class PublicController extends RestApiController{

    /**
     * @Rest\View
     */
    public function ticker(Request $request, $currency){
        /*$lowCurrency = strtolower($currency);

        $providers = array();
        foreach(Currency::$LISTA as $curr){
            if(strtolower($curr) != $lowCurrency){
                $providers[$curr.'x'.strtoupper($currency)] = $this->get('net.telepay.exchange.'.strtolower($curr).'x'.$lowCurrency)->getPrice();
            }

        }

        die(print_r($providers,true));

        $btc2eur = $this->get('net.telepay.exchange.btcx'.$lowCurrency);
        //die(print_r($btc2eur->getPrice()['result']['XXBTZEUR']['a'],true));
        //$btc2eurPrice = $btc2eur->getPrice()->result->XXBTZEUR->b[0];
        $btc2eurPrice = $btc2eur->getPrice()['result']['XXBTZEUR']['a'];



        $combos_eur = array(
            Currency::$BTC.'x'.Currency::$EUR => $this->get('net.telepay.exchange.btcx'.$lowCurrency)->getPrice(),
            Currency::$FAC.'x'.Currency::$EUR => $this->get('net.telepay.exchange.facx'.$lowCurrency)->getPrice(),
            Currency::$MXN.'x'.Currency::$EUR => $this->get('net.telepay.exchange.mxnx'.$lowCurrency)->getPrice(),
            Currency::$PLN.'x'.Currency::$EUR => $this->get('net.telepay.exchange.plnx'.$lowCurrency)->getPrice(),
            Currency::$USD.'x'.Currency::$EUR => $this->get('net.telepay.exchange.usdx'.$lowCurrency)->getPrice()
        );

        $response = 'response';

        return $this->restV2(200, "ok", "Exchange information", $response);*/

        $default_currency = strtoupper($currency);
        $default_currency_scale = $this->_getScale($default_currency);

        $currencies = Currency::$LISTA;

        $result = array();
        foreach($currencies as $currency ){
            if($currency != $default_currency ){
                try{
                    $currency_scale = $this->_getScale($currency);
                    $scale = $currency_scale - $default_currency_scale;
                    $number = pow(10,$scale);
                    $price = $this->_exchange($number, $currency, $default_currency);
                    $result[$currency.'x'.$default_currency] = round($price, $default_currency_scale);
                }catch (HttpException $e){
                    $result[$currency.'x'.$default_currency] = $e->getMessage();
                }

            }
        }

        return $this->restV2(200, "ok", "Exchange info got successfully", $result);

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
            Currency::$PLN,
            Currency::$USD
        ));
    }

    public function _exchange($amount,$curr_in,$curr_out){

        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price = $exchange->getPrice();

        $total = $amount * $price;

        return $total;

    }

    public  function _getScale($currency){

        $scale=0;
        switch($currency){
            case "EUR":
                $scale=2;
                break;
            case "MXN":
                $scale=2;
                break;
            case "USD":
                $scale=2;
                break;
            case "BTC":
                $scale=8;
                break;
            case "FAC":
                $scale=8;
                break;
            case "PLN":
                $scale=2;
                break;
            case "":
                $scale=0;
                break;
        }

        return $scale;

    }
}