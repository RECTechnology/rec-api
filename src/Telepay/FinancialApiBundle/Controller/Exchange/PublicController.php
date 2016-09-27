<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 2:10 AM
 */


namespace Telepay\FinancialApiBundle\Controller\Exchange;

use Symfony\Component\Config\FileLocator;
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
        $default_currency = strtoupper($currency);
        $default_currency_scale = Currency::$SCALE[$default_currency];
        $currencies = Currency::$LISTA;
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
        $default_currency_scale = Currency::$SCALE[$default_currency];
        $currencies = Currency::$LISTA;
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
                    $price_bid = 1.0/$this->_exchange($number, $default_currency, $currency);
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
            Currency::$BTC,
            Currency::$FAC,
            Currency::$MXN,
            Currency::$PLN,
            Currency::$USD
        ));
    }

    /**
     * @Rest\View
     */
    public function maps(Request $request, $service = null){

        if($service != null){
            if($service != 'halcash_es' &&
                $service != 'halcash_pl' &&
                $service != 'teleingreso' &&
                $service != 'all' &&
                $service != 'easypay')
                throw new HttpException(409, 'Service not allowed');
        }

        $configDirectories = array(__DIR__.'/../../Resources/public/json');

        $locator = new FileLocator($configDirectories);
        $halcash_esFile = $locator->locate('halcash_es_points.json', null, false);

        $halcash_es_points = $halcash_esFile[0];
        $str_datos = file_get_contents($halcash_es_points);
        $halcash_es_data = json_decode($str_datos,true);

        $locator_poland = new FileLocator($configDirectories);
        $halcash_plFile = $locator_poland->locate('halcash_pl_points.json', null, false);

        $halcash_pl_points = $halcash_plFile[0];
        $str_datos_poland = file_get_contents($halcash_pl_points);
        $halcash_pl_data = json_decode($str_datos_poland,true);

        $locator = new FileLocator($configDirectories);
        $easypayFile = $locator->locate('easypay_points.json', null, false);

        $easypay_points = $easypayFile[0];
        $str_datos_easypay = file_get_contents($easypay_points);
        $easypay_data = json_decode($str_datos_easypay,true);

        $locator = new FileLocator($configDirectories);
        $abancaFile = $locator->locate('abanca_points.json', null, false);

        $abanca_points = $abancaFile[0];
        $str_datos_abanca = file_get_contents($abanca_points);
        $abanca_data = json_decode($str_datos_abanca,true);

        $abancaRequestedAtm = array();
        foreach($abanca_data as $abanca){
            $abanca_response = array(
                'postal_code'   =>  $abanca['properties']['postcode'],
                'coordinates'   =>  array(
                    'lng'   =>  $abanca['geometry']['coordinates'][0],
                    'lat'   =>  $abanca['geometry']['coordinates'][1]
                ),
                'bank'  =>  $abanca['properties']['name'],
                'address'   =>  $abanca['properties']['address']
            );

            $abancaRequestedAtm[] = $abanca_response;

        }

        $requestedAtms = array(
            'halcash_es'    =>  $halcash_es_data,
            'halcash_pl'    =>  $halcash_pl_data,
            'teleingreso'   =>  $abancaRequestedAtm,
            'easypay'       =>  $easypay_data
        );

        if($service == 'all'){
            $response = $requestedAtms;
        }else{
            $response = $requestedAtms[$service];
        }

        return $this->rest(200,"Ok", $response);

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

}