<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/22/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Controller\SwiftResponse;

class AdapterController extends RestApiController{

    public function make(Request $request, $type_in, $type_out){

        if($type_in == 'btc'){

            if($type_out == 'halcash'){

                return $this->_btcHalcash($request);

            }elseif($type_out == 'cryptocapital'){



            }elseif($type_out == 'bank_transfer'){

            }else{

            }

        }elseif($type_in == 'fac'){

        }else{

        }

    }

    public function check(Request $request, $version_number, $type_in, $type_out, $id){

        if($type_in == 'btc'){

            if($type_out == 'halcash'){

                return $this->_btcHalcashCheck($id);

            }elseif($type_out == 'cryptocapital'){



            }elseif($type_out == 'bank_transfer'){

            }else{

            }

        }elseif($type_in == 'fac'){

        }else{

        }

    }

    public function update(Request $request, $version_number, $type_in, $type_out, $id){



    }

    private function _receiver(Request $request, $paramNames){
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName)) throw new HttpException(400, "Missing param '".$paramName."'");
            $params[$paramName] = $request->request->get($paramName);
        }

        return $params;

    }

    private function _btcHalcash(Request $request){

        //country, phone_number, amount, prefix
        $paramNames = array(
            'amount',
            'phone_number',
            'phone_prefix',
            'country'
        );

        $params = $this->_receiver($request, $paramNames);

        $request->request->remove('phone_number');
        $request->request->remove('country');
        $request->request->remove('phone_prefix');
        $request->request->remove('amount');

        $request->request->add(array(
            'phone' =>  $params['phone_number'],
            'prefix'    =>  $params['phone_prefix'],
            'amount'    =>  $params['amount']*100,
            'description'   =>  'description'
        ));

        $method_in = 'btc';
        if($params['country'] == 'ES'){
            $meyhod_out = 'halcash_es';
        }else{
            $meyhod_out = 'halcash_pl';
        }

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $meyhod_out
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status, ticcket_id, id, address,amount, pin
            $customResponse = array();
            $customResponse['status'] = 'ok';
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['pin'] = $array_response['pay_out_info']['pin'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _btcHalcashCheck($id){

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'btc',
            'type_out'  =>  'halcash_es',
            'id'    =>  $id
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status,created,ticket_id,id,type,orig_coin,orig_scale,orig_amount,dst_coin,dst_scale,
            //dst_amount,price,address,confirmations,received,phone,prefix,pin

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'pending';
            }else{
                $customResponse['status'] = $array_response['status'];
            }

            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'btc_halcash';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            //TODO calcular bien el precio
            $customResponse['price'] = ($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']*1e8);
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['phone'] = $array_response['pay_out_info']['phone'];
            $customResponse['prefix'] = $array_response['pay_out_info']['prefix'];
            //TODO El pin no se ha generado todavia
            $customResponse['pin'] = $array_response['pay_out_info']['pin'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }


}


