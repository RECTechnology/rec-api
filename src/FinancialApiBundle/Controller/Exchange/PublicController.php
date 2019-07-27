<?php

namespace App\FinancialApiBundle\Controller\Exchange;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\Financial\Currency;
use FOS\RestBundle\Controller\Annotations as Rest;

class PublicController extends RestApiController{

    /**
     * @Rest\View
     */
    public function ticker(Request $request, $currency){
        $default_currency = strtoupper($currency);
        $currencies = Currency::$LISTA;
        if(!in_array($default_currency, $currencies)) throw new HttpException(404, 'Currency '.$default_currency.' not found');
        $default_currency_scale = Currency::$SCALE[$default_currency];
        $result = array();
        foreach($currencies as $currency ){
            if($currency != $default_currency ){
                try{
                    $currency_scale = Currency::$SCALE[$currency];
                    $scale = $currency_scale - $default_currency_scale;
                    $number = pow(10,$scale);
                    $price = $this->_exchange($number, $currency, $default_currency);
                    $result[$currency.'x'.$default_currency] = round($price, $default_currency_scale + 1);
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
    public function tickerV2(Request $request, $currency){

        $default_currency = strtoupper($currency);
        $currencies = Currency::$LISTA;
        if(!in_array($default_currency, $currencies)) throw new HttpException(404, 'Currency '.$default_currency.' not found');
        $default_currency_scale = Currency::$SCALE[$default_currency];

        $result = array();
        $ask = array();
        $bid = array();
        foreach($currencies as $currency ){
            if($currency != $default_currency ){
                try{
                    $currency_scale = Currency::$SCALE[$currency];
                    $scale = $currency_scale - $default_currency_scale;
                    $number = pow(10,$scale);
                    $price_ask = $this->_exchange($number, $currency, $default_currency);
                    $ask[$currency.'x'.$default_currency] = round($price_ask, $default_currency_scale + 1);
                    $price_bid = $this->_exchangeInverse($number, $currency, $default_currency);
                    $bid[$currency.'x'.$default_currency] = round($price_bid, $default_currency_scale + 1);
                    $result[$currency.'x'.$default_currency] = round(($price_ask + $price_bid)/2, $default_currency_scale + 1);
                }catch (HttpException $e){
                    $result[$currency.'x'.$default_currency] = $e->getMessage();
                }
            }
        }
        return $this->restV2(200, "ok", "Exchange info got successfully", array(
                'av' => $result,
                'ask' => $ask,
                'bid' => $bid
            )
        );
    }

    /**
     * @Rest\View
     */
    public function currencies(Request $request){
        return $this->restV2(200, "ok", "Currency information", array(
            Currency::$EUR,
            Currency::$REC
        ));
    }

    public function _exchange($amount,$curr_in,$curr_out){
        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('FinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );
        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);
        $price = $exchange->getPrice();
        $total = $amount * $price;
        return $total;
    }

    public function _exchangeInverse($amount,$curr_in,$curr_out){
        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('FinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_out,'dst'=>$curr_in),
            array('id'=>'DESC')
        );
        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);
        $price = 1.0/($exchange->getPrice());
        $total = $amount * $price;
        return $total;
    }
}