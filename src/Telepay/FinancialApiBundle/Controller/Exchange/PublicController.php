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
            if($service != 'halcash_es' && $service != 'halcash_pl' && $service != 'teleingreso' && $service != 'all' )
                throw new HttpException(409, 'Service not allowed');
        }

        $configDirectories = array(__DIR__.'/../../Resources/public/json');

        $locator = new FileLocator($configDirectories);
        $halcash_esFile = $locator->locate('halcash_points_sp.json', null, false);

        $halcash_es_points = $halcash_esFile[0];
        $str_datos = file_get_contents($halcash_es_points);
        $halcash_es_data = json_decode($str_datos,true);

        $locator_poland = new FileLocator($configDirectories);
        $halcash_plFile = $locator_poland->locate('halcash_points_pln.json', null, false);

        $halcash_pl_points = $halcash_plFile[0];
        $str_datos_poland = file_get_contents($halcash_pl_points);
        $halcash_pl_data = json_decode($str_datos_poland,true);

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
                    $abanca['geometry']['coordinates'][0], $abanca['geometry']['coordinates'][1]
                ),
                'bank'  =>  $abanca['properties']['name'],
                'address'   =>  $abanca['properties']['address']
            );

            $abancaRequestedAtm[] = $abanca_response;

        }

        $requestedAtms = array(
            'halcash_es'    =>  $halcash_es_data,
            'halcash_pl'    =>  $halcash_pl_data,
            'teleingreso'   =>  $abancaRequestedAtm
        );

        if($service == 'all'){
            $response = $requestedAtms;
        }else{
            $response = $requestedAtms[$service];
        }
//        foreach($halcash_pl_data as $atm){
//
//            if($atm['coordinates'][0] >= $request->get('lat_sw') and
//                $atm['coordinates'][0] <= $request->get('lat_ne') and
//                $atm['coordinates'][1] >= $request->get('lon_sw') and
//                $atm['coordinates'][1] <= $request->get('lon_ne')){
//                //TODO get the city
//                $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$atm['postal_code'];
//                $ch = curl_init($url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                $cities = curl_exec($ch);
//                curl_close($ch);
//
//                $cities = json_decode($cities, true);
//
//                $cities = $cities['results'];
//                foreach($cities as $city){
//                    foreach($city['address_components'] as $component){
//
//                        if($component['types'][0] == 'country' && $component['short_name'] == 'ES'){
//                            $atm['city'] = $city['address_components'][1]['long_name'];
//                        }
//                    }
//
//                }
//
//                $atm['country'] = 'ES';
//                $requestedAtms[] = $atm;
//            }
//
//        }

//        die(print_r($requestedAtms,true));
//        foreach($datos_poland as $atm_poland){
//            if($atm_poland['coordinates'][0] >= $request->get('lat_sw') and
//                $atm_poland['coordinates'][0] <= $request->get('lat_ne') and
//                $atm_poland['coordinates'][1] >= $request->get('lon_sw') and
//                $atm_poland['coordinates'][1] <= $request->get('lon_ne')){
//                $atm_poland['country'] = 'PL';
//                $requestedAtms[]=$atm_poland;
//            }
//
//        }

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