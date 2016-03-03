<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Telepay\FinancialApiBundle\Document\Transaction;

class RestApiController extends FosRestController{

    protected function buildRestView($code, $message, $data){
        return $this->view(new ApiResponse($code, $message, $data), $code);
    }

    protected function rest($code, $message = "No info", $data = array()){
        return $this->handleView($this->view(new ApiResponse($code, $message, $data), $code));
    }

    protected function restV2($httpCode, $status, $message = "No info", $data = array()){
        return $this->handleView($this->view(new ApiResponseV2($status, $message, $data), $httpCode));
    }

    protected function restV3($httpCode, $status, $message = "No info", $id, $amount, $scale, $currency, $updated, $data = array()){
        return $this->handleView($this->view(
            new ApiResponseV3($status, $message, $id, $amount, $scale, $currency , $updated, $data),
            $httpCode
        ));
    }

    protected function restSwift($httpCode, $status, $message = "No info", $id, $amount, $scale, $currency,$created, $updated, $pay_in_info = array(),$pay_out_info = array() ){
        return $this->handleView($this->view(
            new SwiftResponse($status, $message, $id, $amount, $scale, $currency ,$created, $updated, $pay_in_info,$pay_out_info),
            $httpCode
        ));
    }

    protected function restMethod($httpCode, $status, $message = "No info", $id, $amount, $scale, $currency,$created, $updated, $pay_in_info = array(), $pay_out_info = array() ){
        return $this->handleView($this->view(
            new MethodResponse($status, $message, $id, $amount, $scale, $currency ,$created, $updated, $pay_in_info, $pay_out_info),
            $httpCode
        ));
    }

    protected function posMethod($httpCode, $status, $message = "No info", $id, $amount, $scale, $currency,$created, $updated, $type, $pay_in_info = array()){
        return $this->handleView($this->view(
            new POSResponse($status, $message, $id, $amount, $scale, $currency ,$created, $updated, $type, $pay_in_info),
            $httpCode
        ));
    }

    protected function restPlain($code, $data = array()){
        return $this->handleView($this->view($data, $code));
    }



    protected function restTransaction(Transaction $transaction, $message = "No info"){
        return $this->restV3(
            200,
            $transaction->getStatus(),
            $message,
            $transaction->getId(),
            $transaction->getAmount(),
            $transaction->getScale(),
            $transaction->getCurrency(),
            $transaction->getUpdated(),
            $transaction->getDataOut()
        );
    }

    protected function swiftTransaction(Transaction $transaction, $message = "No info"){
        return $this->restSwift(
            200,
            $transaction->getStatus(),
            $message,
            $transaction->getId(),
            $transaction->getAmount(),
            $transaction->getScale(),
            $transaction->getCurrency(),
            $transaction->getCreated(),
            $transaction->getUpdated(),
            $transaction->getPayInInfo(),
            $transaction->getPayOutInfo()
        );
    }

    protected function methodTransaction($code, Transaction $transaction, $message = "No info"){
        return $this->restMethod(
            $code,
            $transaction->getStatus(),
            $message,
            $transaction->getId(),
            $transaction->getAmount(),
            $transaction->getScale(),
            $transaction->getCurrency(),
            $transaction->getCreated(),
            $transaction->getUpdated(),
            $transaction->getPayInInfo(),
            $transaction->getPayOutInfo()
        );
    }

    protected function posTransaction($code, Transaction $transaction, $message = "No info"){
        return $this->posMethod(
            $code,
            $transaction->getStatus(),
            $message,
            $transaction->getId(),
            $transaction->getAmount(),
            $transaction->getScale(),
            $transaction->getCurrency(),
            $transaction->getCreated(),
            $transaction->getUpdated(),
            $transaction->getType(),
            $transaction->getPayInInfo()
        );
    }

}