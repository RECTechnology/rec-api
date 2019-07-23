<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
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

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        $default_currency = $userGroup->getDefaultCurrency();

        $currencies = Currency::$LISTA;

        $result = array();
        $result['default_currency'] = $default_currency;
        $result['scale'] = Currency::$SCALE[$default_currency];
        foreach($currencies as $currency ){
            if($currency != $default_currency ){
                try{
                    $price = $this->_exchange(1, $default_currency, $currency);
                    $result[$currency] = $price;
                }catch (HttpException $e){
                    $result[$currency] = $e->getMessage();
                }

            }
        }

        return $this->restV2(200, "ok", "Exchange info got successfully", $result);

    }


    public function _exchange($amount,$curr_in,$curr_out){

        $dm = $this->getDoctrine()->getManager();
        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
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