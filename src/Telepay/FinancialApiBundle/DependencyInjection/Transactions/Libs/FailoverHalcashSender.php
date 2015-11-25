<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;


class FailoverHalcashSender extends HalcashAbancaSender {

    private $telepayHalcash;

    function __construct($user, $password, $alias, $url, $abancaHalcashDriver) {
        parent::__construct($user, $password, $alias, $url, $abancaHalcashDriver);
        $this->telepayHalcash = new Halcash($user, $password, $alias, $url);
    }

    public function sendV3($phone, $prefix, $amount, $reference, $pin, $transaction_id) {
        $telepayResponse = $this->telepayHalcash->sendV3($phone, $prefix, $amount, $reference, $pin, $transaction_id);
        if(!$telepayResponse || $telepayResponse['errorcode'] != "0")
            return parent::sendV3($phone, $prefix, $amount, $reference, $pin, $transaction_id);
        else
            return $telepayResponse;
    }

}