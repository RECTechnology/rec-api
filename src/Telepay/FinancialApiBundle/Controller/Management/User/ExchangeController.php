<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class ExchangeController
 * @package Telepay\FinancialApiBundle\Controller\Management\User
 */
class ExchangeController extends RestApiController{


    /**
     * reads information about exchange
     */
    public function read(){

        $user = $this->get('security.context')->getToken()->getUser();

        $default_currency = $user->getDefaultCurrency();

        $currencies = Currency::$LISTA;

        $result = array();
        $result['default_currency'] = $default_currency;
        $result['scale'] = $this->_getScale($default_currency);
        foreach($currencies as $currency ){
            if($currency != $default_currency ){
                try{
                    $price = $this->_exchange(1, $default_currency, $currency);
                    $result[$currency]=$price;
                }catch (HttpException $e){
                    $result[$currency] = $e;
                }

            }
        }

        return $this->restV2(200, "ok", "Exchange info got successfully", $result);

    }


    public function _exchange($amount,$curr_in,$curr_out){

        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price=$exchange->getPrice();

        $total=$amount*$price;

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
                $scale=8;
                break;
            case "":
                $scale=0;
                break;
        }

        return $scale;

    }

}