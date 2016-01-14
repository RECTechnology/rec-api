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

class AdapterController extends RestApiController{

    public function make(Request $request, $type_in, $type_out){

        if($type_in == 'btc'){

            if($type_out == 'halcash'){

                return $this->_btcHalcash($request);

            }elseif($type_out == 'cryptocapital'){

                return $this->_btcCryptocapital($request);

            }elseif($type_out == 'bank_transfer'){

                return $this->_btcBankTransfer($request);
            }else{

            }

        }elseif($type_in == 'fac'){
            if($type_out == 'halcash'){

                return $this->_facHalcash($request);

            }elseif($type_out == 'cryptocapital'){

                return $this->_facCryptocapital($request);

            }elseif($type_out == 'bank_transfer'){

                return $this->_facBankTransfer($request);

            }else{

            }
        }elseif($type_in == 'paynet'){
            if($type_out == 'btc'){

                return $this->_paynetBtc($request);

            }elseif($type_out == 'fac'){

                return $this->_paynetFac($request);

            }else{

            }

        }else{

        }

    }

    public function check(Request $request, $version_number, $type_in, $type_out, $id){

        if($type_in == 'btc'){
            if($type_out == 'halcash'){

                return $this->_btcHalcashCheck($id);

            }elseif($type_out == 'cryptocapital'){

                return $this->_btcCryptocapitalCheck($id);

            }elseif($type_out == 'bank_transfer'){

                return $this->_btcBankTransferCheck($id);

            }else{

            }

        }elseif($type_in == 'fac'){
            if($type_out == 'halcash'){

                return $this->_facHalcashCheck($id);

            }elseif($type_out == 'cryptocapital'){

                return $this->_facCryptocapitalCheck($id);

            }elseif($type_out == 'bank_transfer'){

                return $this->_facBankTransferCheck($id);

            }else{

            }
        }elseif($type_in == 'paynet'){
            if($type_out == 'btc'){

                return $this->_paynetBtcCheck($id);

            }elseif($type_out == 'fac'){

                return $this->_paynetFacCheck($id);

            }else{

            }
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
            $method_out = 'halcash_es';
        }else{
            $method_out = 'halcash_pl';
        }

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
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

        $dst_coin = 'eur';

        //If not found check if is a polsky transaction
        if($response->getStatusCode() == 404){
            $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
                'version_number'    =>  1,
                'type_in'   =>  'btc',
                'type_out'  =>  'halcash_pl',
                'id'    =>  $id
            ));

            $dst_coin = 'pln';
        }

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status,created,ticket_id,id,type,orig_coin,orig_scale,orig_amount,dst_coin,dst_scale,
            //dst_amount,price,address,confirmations,received,phone,prefix,pin

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'pending';
            }elseif($array_response['status'] == 'success'){
                $customResponse['status'] = 'sent';
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
            $customResponse['dst_coin'] = $dst_coin;
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2);
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['phone'] = $array_response['pay_out_info']['phone'];
            $customResponse['prefix'] = $array_response['pay_out_info']['prefix'];
            $customResponse['pin'] = $array_response['pay_out_info']['pin'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _facHalcash(Request $request){

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

        $method_in = 'fac';
        if($params['country'] == 'ES'){
            $method_out = 'halcash_es';
        }else{
            $method_out = 'halcash_pl';
        }

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
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

    private function _facHalcashCheck($id){

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'fac',
            'type_out'  =>  'halcash_es',
            'id'    =>  $id
        ));

        $dst_coin = 'eur';

        //If not found check if is a polsky transaction
        if($response->getStatusCode() == 404){
            $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
                'version_number'    =>  1,
                'type_in'   =>  'btc',
                'type_out'  =>  'halcash_pl',
                'id'    =>  $id
            ));

            $dst_coin = 'pln';
        }

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
            $customResponse['type'] = 'fac_halcash';
            $customResponse['orig_coin'] = 'fac';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = $dst_coin;
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2);
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['phone'] = $array_response['pay_out_info']['phone'];
            $customResponse['prefix'] = $array_response['pay_out_info']['prefix'];
            $customResponse['pin'] = $array_response['pay_out_info']['pin'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _paynetBtc(Request $request){

        //description, btc_address, amount
        $paramNames = array(
            'amount',
            'btc_address',
            'description'
        );

        $params = $this->_receiver($request, $paramNames);

        $request->request->remove('btc_address');
        $request->request->remove('amount');

        $request->request->add(array(
            'address'    =>  $params['btc_address'],
            'amount'    =>  $params['amount']*100,
            'description'   =>  $params['description']
        ));

        $method_in = 'paynet_reference';
        $method_out = 'btc';

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status, ticcket_id, id, address,amount, pin
            $customResponse = array();
            $customResponse['status'] = 'ok';
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['barcode'] = $array_response['pay_in_info']['barcode'];
            $customResponse['url'] = "https://www.datalogic.com.mx/PaynetCE/GetBarcodeImage.pn?text=".$array_response['pay_in_info']['barcode']."&bh=50&bw=1";
            $customResponse['amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['expiration_date'] = $array_response['pay_in_info']['expires_in'];
//            $customResponse['description'] = $array_response['pay_in_info']['description'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _paynetFac(Request $request){
        //description, fac_address, amount
        $paramNames = array(
            'amount',
            'fac_address',
            'description'
        );

        $params = $this->_receiver($request, $paramNames);

        $request->request->remove('fac_address');
        $request->request->remove('amount');

        $request->request->add(array(
            'address'    =>  $params['fac_address'],
            'amount'    =>  $params['amount']*100,
            'description'   =>  $params['description']
        ));

        $method_in = 'paynet_reference';
        $method_out = 'fac';

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status, ticcket_id, id, address,amount, pin
            $customResponse = array();
            $customResponse['status'] = 'ok';
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['barcode'] = $array_response['pay_in_info']['barcode'];
            $customResponse['url'] = "https://www.datalogic.com.mx/PaynetCE/GetBarcodeImage.pn?text=".$array_response['pay_in_info']['barcode']."&bh=50&bw=1";
            $customResponse['amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['expiration_date'] = $array_response['pay_in_info']['expires_in'];
            $customResponse['description'] = $array_response['pay_in_info']['description'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }
    }

    private function _paynetBtcCheck($id){

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'paynet_reference',
            'type_out'  =>  'btc',
            'id'    =>  $id
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status,created,ticket_id,id,type,orig_coin,orig_scale,orig_amount,dst_coin,dst_scale,
            //dst_amount,price,address,confirmations,received,phone,prefix,pin

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'ok';
            }else{
                $customResponse['status'] = $array_response['status'];
            }

            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['expired'] = $array_response['pay_in_info']['expires_in'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _paynetFacCheck($id){

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'paynet_reference',
            'type_out'  =>  'fac',
            'id'    =>  $id
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status,created,ticket_id,id,type,orig_coin,orig_scale,orig_amount,dst_coin,dst_scale,
            //dst_amount,price,address,confirmations,received,phone,prefix,pin

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'ok';
            }else{
                $customResponse['status'] = $array_response['status'];
            }

            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['expired'] = $array_response['pay_in_info']['expires_in'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _btcBankTransfer(Request $request){

        $paramNames = array(
            'beneficiary',
            'iban',
            'amount',
            'bic_swift',
            'concept'
        );

        $params = $this->_receiver($request, $paramNames);

        $request->request->remove('concept');
        $request->request->remove('amount');

        $request->request->add(array(
            'amount'    =>  $params['amount']*100,
            'description'   =>  $params['concept']
        ));

        $method_in = 'btc';

        $method_out = 'sepa';

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
        ));


        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status, ticcket_id, id, address,amount, pin
            $customResponse = array();
            $customResponse['status'] = 'ok';
            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'btc_bank_transfer';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2)*100;
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['message'] = 'After the payment you will receive the transfer during the next 24/48h.';

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _btcBankTransferCheck($id){

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'btc',
            'type_out'  =>  'sepa',
            'id'    =>  $id
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'pending';
            }else{
                $customResponse['status'] = $array_response['status'];
            }

            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'btc_bank_transfer';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2);
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['beneficiary'] = $array_response['pay_in_info']['beneficiary'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }
    }

    private function _facBankTransfer(Request $request){
        $paramNames = array(
            'beneficiary',
            'iban',
            'amount',
            'bic_swift',
            'concept'
        );

        $params = $this->_receiver($request, $paramNames);

        $request->request->remove('concept');
        $request->request->remove('amount');

        $request->request->add(array(
            'amount'    =>  $params['amount']*100,
            'description'   =>  $params['concept']
        ));

        $method_in = 'fac';

        $method_out = 'sepa';

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
        ));


        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status, ticcket_id, id, address,amount, pin
            $customResponse = array();
            $customResponse['status'] = 'ok';
            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'btc_bank_transfer';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2)*100;
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['message'] = 'After the payment you will receive the transfer during the next 24/48h.';

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }
    }

    private function _facBankTransferCheck($id){
        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'fac',
            'type_out'  =>  'sepa',
            'id'    =>  $id
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'pending';
            }else{
                $customResponse['status'] = $array_response['status'];
            }

            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'fac_bank_transfer';
            $customResponse['orig_coin'] = 'fac';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2)*100;
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['beneficiary'] = $array_response['pay_in_info']['beneficiary'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }
    }

    private function _btcCryptocapital(Request $request){

        $paramNames = array(
            'amount',
            'email'
        );

        $params = $this->_receiver($request, $paramNames);

        $request->request->remove('amount');

        $request->request->add(array(
            'amount'    =>  $params['amount']*100
        ));

        $method_in = 'btc';

        $method_out = 'cryptocapital';

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
        ));


        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status, ticcket_id, id, address,amount, pin
            $customResponse = array();
            $customResponse['status'] = 'ok';
            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'btc_cryptocapital';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2)*100;
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['message'] = 'After the payment you will receive an email with the instructions.';

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _btcCryptocapitalCheck($id){

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'btc',
            'type_out'  =>  'cryptocapital',
            'id'    =>  $id
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'pending';
            }else{
                $customResponse['status'] = $array_response['status'];
            }

            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'btc_cryptocapital';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2)*100;
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['email'] = $array_response['pay_in_info']['email'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }
    }

    private function _facCryptocapital(Request $request){

        $paramNames = array(
            'amount',
            'email'
        );

        $params = $this->_receiver($request, $paramNames);

        $request->request->remove('amount');

        $request->request->add(array(
            'amount'    =>  $params['amount']*100
        ));

        $method_in = 'fac';

        $method_out = 'cryptocapital';

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::make', array(
            'request'  => $request,
            'version_number' => '1',
            'type_in'   =>  $method_in,
            'type_out'  =>  $method_out
        ));


        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){
            //status, ticcket_id, id, address,amount, pin
            $customResponse = array();
            $customResponse['status'] = 'ok';
            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'fac_cryptocapital';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2)*100;
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['message'] = 'After the payment you will receive an email with the instructions.';

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }

    }

    private function _facCryptocapitalCheck($id){

        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::check', array(
            'version_number'    =>  1,
            'type_in'   =>  'fac',
            'type_out'  =>  'cryptocapital',
            'id'    =>  $id
        ));

        $array_response = json_decode($response->getContent(), true);
        if($response->getStatusCode() == 200){

            $customResponse = array();
            if($array_response['status'] == 'created'){
                $customResponse['status'] = 'pending';
            }else{
                $customResponse['status'] = $array_response['status'];
            }

            $customResponse['created'] = $array_response['created'];
            $customResponse['ticket_id'] = $array_response['id'];
            $customResponse['id'] = $array_response['id'];
            $customResponse['type'] = 'btc_cryptocapital';
            $customResponse['orig_coin'] = 'btc';
            $customResponse['orig_scale'] = 100000000;
            $customResponse['orig_amount'] = $array_response['pay_in_info']['amount'];
            $customResponse['dst_coin'] = 'eur';
            $customResponse['dst_scale'] = 100;
            $customResponse['dst_amount'] = $array_response['pay_out_info']['amount'];
            $customResponse['price'] = round(($array_response['pay_out_info']['amount']/100)/($array_response['pay_in_info']['amount']/1e8),2)*100;
            $customResponse['address'] = $array_response['pay_in_info']['address'];
            $customResponse['confirmations'] = $array_response['pay_in_info']['confirmations'];
            $customResponse['received'] = $array_response['pay_in_info']['received'];
            $customResponse['email'] = $array_response['pay_in_info']['email'];

            return $this->restPlain($response->getStatusCode(), $customResponse);

        }else{
            return $this->restPlain($response->getStatusCode(), $array_response);
        }
    }

    public function status($version, $currency){

        $status_btc_halcash_eur = 'instant';
        $status_btc_halcash_pln = 'instant';
        $status_paynet_btc = 'instant';
        $status_btc_cryptocapital = 'instant';
        $status_transfer_btc = 'delay';
        $status_btc_transfer = 'unavailable';
        $status_pos_btc = 'unavailable';

        $response = array(
            'btc_halcash'   =>  array(
                'origin'    =>  'bitcoin',
                'destination'   =>  'halcash',
                'text'      =>  'Trade in your bitcoins for euros or zlotys in atms conventional. ',
                'countries' =>  'Spain, Poland',
                'status'    =>  ($status_btc_halcash_eur == 'instant' || $status_btc_halcash_pln == 'instant')?'instant':'delay',
                'eur'   =>  array(
                    'status'    =>  $status_btc_halcash_eur,
                    'message'   =>  $this->_getStatusMessage($status_btc_halcash_eur)['message'],
                    'delay'     =>  $this->_getStatusMessage($status_btc_halcash_eur)['delay']
                ),
                'pln'   =>  array(
                    'status'    =>  $status_btc_halcash_pln,
                    'message'   =>  $this->_getStatusMessage($status_btc_halcash_pln)['message'],
                    'delay'   =>  $this->_getStatusMessage($status_btc_halcash_pln)['delay']
                )

            ),
            'paynet_btc'    =>  array(
                'status'    =>  $status_paynet_btc,
                'message'   =>  $this->_getStatusMessage($status_paynet_btc)['message'],
                'delay'   =>  $this->_getStatusMessage($status_paynet_btc)['delay'],
                'origin'    =>  'paynet',
                'destination'   =>  'bitcoin',
                'text'      =>  'Buy bitcoins with your pesos. This method generates a reference that is paid in a store. After we deposit the bitcoins in your wallet.',
                'countries' =>  'México'
            ),
            'btc_cryptocapital'    =>  array(
                'status'    =>  $status_btc_cryptocapital,
                'message'   =>  $this->_getStatusMessage($status_btc_cryptocapital)['message'],
                'delay'   =>  $this->_getStatusMessage($status_btc_cryptocapital)['delay'],
                'origin'    =>  'bitcoin',
                'destination'   =>  'cryptocapital',
                'text'      =>  'Sell your bitcoins and obtain a virtual card to buy over the world.',
                'countries' =>  'All over the world'
            ),
            'transfer_btc'    =>  array(
                'status'    =>  $status_transfer_btc,
                'message'   =>  $this->_getStatusMessage($status_transfer_btc)['message'],
                'delay'   =>  $this->_getStatusMessage($status_transfer_btc)['delay'],
                'origin'    =>  'transfer',
                'destination'   =>  'bitcoin',
                'text'      =>  'Buy bitcoins with a bank transfer.',
                'countries' =>  'All over the world'
            ),
            'pos_btc'    =>  array(
                'status'    =>  $status_pos_btc,
                'message'   =>  $this->_getStatusMessage($status_pos_btc)['message'],
                'delay'   =>  $this->_getStatusMessage($status_pos_btc)['delay'],
                'origin'    =>  'bankcard',
                'destination'   =>  'bitcoin',
                'text'      =>  'Buy bitcoins with your bank card.',
                'countries' =>  'All over the world'
            ),
            'btc_transfer'    =>  array(
                'status'    =>  $status_btc_transfer,
                'message'   =>  $this->_getStatusMessage($status_btc_transfer)['message'],
                'delay'   =>  $this->_getStatusMessage($status_btc_transfer)['delay'],
                'origin'    =>  'bitcoin',
                'destination'   =>  'transfer',
                'text'      =>  'Sell bitcoins and receive a bank transfer.',
                'countries' =>  'Eurozone'
            )
        );

        return $this->restPlain(200, $response);

    }

    public function hello($version){

//        $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\SwiftController::hello', array(
//            'version_number'    =>  1,
//            'currency'      =>  'btc'
//        ));
//
//        $array_response = json_decode($response->getContent(), true);
//
//        $swift_methods = $array_response['swift_methods'];

        $btc_sell_price = round(($this->_exchange(100000000, 'btc', 'eur')/100)*0.95,2);
        $btc_buy_price = round($btc_sell_price *1.05,2);
        $pln_price = round(($this->_exchange(100000000, 'btc', 'pln')/100)*0.95,2);
        $mxn_price = round(($this->_exchange(100, 'mxn', 'btc')/100)*1.05,2);
        $variable_fee = 0;
        $fixed_fee = 0;
        $paynet_variable_fee = 0;
        $paynet_fixed_fee = 0;
        $timeout = 1200;
        $daily_sell_limit = 600;
        $daily_buy_limit = 0;
        $monthly_sell_limit = 3000;
        $monthly_buy_limit = 0;
        $daily_limit_pln = 2000;
        $daily_buy_limit_pln = 0;
        $monthly_limit_pln = 20000;
        $monthly_buy_limit_pln = 0;
        $paynet_limit = 0;
        $paynet_buy_limit = 1000;
        $cryptocapital_limit = 250;
        $confirmations = 1;
        $terms = "http://www.chip-chap.com/legal.html";
        $title = "ChipChap";

        $buy_price = array(
            'EUR'   =>  $btc_buy_price,
            'PLN'   =>  $pln_price * $btc_buy_price,
            'MXN'   =>  $mxn_price * $btc_buy_price
        );

        $sell_price = array(
            'EUR'   =>  $btc_sell_price,
            'PLN'   =>  $pln_price * $btc_sell_price,
            'MXN'   =>  $mxn_price * $btc_sell_price
        );

        $buy_limits = array(
            'day'   =>  array(
                'EUR'   =>  $daily_buy_limit,
                'PLN'   =>  $daily_buy_limit_pln
            ),
            'month'   =>  array(
                'EUR'   =>  $monthly_buy_limit,
                'PLN'   =>  $monthly_buy_limit_pln
            ),
            'paynet'    =>  array(
                'MXN'   =>  $paynet_buy_limit
            )
        );

        $sell_limits = array(
            'day'   =>  array(
                'EUR'   =>  $daily_sell_limit,
                'PLN'   =>  $daily_limit_pln
            ),
            'month'   =>  array(
                'EUR'   =>  $monthly_sell_limit,
                'PLN'   =>  $monthly_limit_pln
            ),
            'paynet'    =>  array(
                'MXN'   =>  $paynet_limit
            ),
            'cryptocapital' =>  array(
                'EUR'   =>  $cryptocapital_limit
            )
        );

        $eur_buy_values = array();
        for($i = 10; $i <= $daily_buy_limit; $i += 10){
            array_push($eur_buy_values, $i);

        }

        $eur_sell_values = array();
        for($i = 10; $i <= $daily_sell_limit; $i += 10){
            array_push($eur_sell_values, $i);

        }

        $pln_buy_values = array();
        for($j = 50 ; $j <= $daily_buy_limit_pln; $j += 50){
            array_push($pln_buy_values, $j);

        }

        $pln_sell_values = array();
        for($j = 50; $j <= $daily_limit_pln; $j += 50){
            array_push($pln_sell_values, $j);

        }

        $paynet_sell_values = array();
        for($p = 100; $p <= $paynet_limit; $p += 100){
            array_push($paynet_sell_values, $p);

        }

        $paynet_buy_values = array();
        for($p = 100; $p <= $paynet_buy_limit; $p += 100){
            array_push($paynet_buy_values, $p);

        }

        $cryptocapital_sell_values = array();
        for($c = 10;$c <= $cryptocapital_limit; $c += 10){
            array_push($cryptocapital_sell_values, $c);

        }
        $buy_values = array(
            'EUR'   =>  $eur_buy_values,
            'PLN'   =>  $pln_buy_values,
            'MXN'   =>  $paynet_buy_values
        );

        $sell_values = array(
            'EUR'   =>  $eur_sell_values,
            'PLN'   =>  $pln_sell_values,
            'MXN'   =>  $paynet_sell_values,
            'CRYPTOCAPITAL' =>  $cryptocapital_sell_values
        );

        $fees = array(
            'fixed'     =>  array(
                'EUR'   =>  $fixed_fee,
                'PLN'   =>  $fixed_fee*$pln_price,
                'MXN'   =>  $paynet_fixed_fee
            ),
            'variable'  =>  array(
                'EUR'   =>  $variable_fee,
                'PLN'   =>  $variable_fee,
                'MXN'   =>  $paynet_variable_fee
            )
        );

        $resp = array(
            'buy_price' =>  $buy_price,
            'sell_price' =>  $sell_price,
            'buy_limits' =>  $buy_limits,
            'sell_limits' =>  $sell_limits,
            'buy_values' =>  $buy_values,
            'sell_values' =>  $sell_values,
            'fees' =>  $fees,
            'confirmations' =>  $confirmations,
            'timeout' =>  $timeout,
            'terms' =>  $terms,
            'title' =>  $title
        );

        return $this->restPlain(200, $resp);


    }

    private function _exchange($amount,$curr_in,$curr_out){

        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price = $exchange->getPrice();
        $total = round($amount * $price,0);

        return $total;

    }

    private  function _getStatusMessage($status){

        if($status == 'delay'){
            $message = 'Service available with delay. (24/48h)';
            $delay = 48;
        }elseif($status == 'instant'){
            $message = 'Instant Service';
            $delay = 0;
        }elseif($status == 'unavailable'){
            $message = 'Service temporally unavailable';
            $delay = '-';
        }

        $response = array(
            'message'   =>  $message,
            'delay' =>  $delay
        );

        return $response;

    }

}


