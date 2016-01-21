<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

use Symfony\Component\HttpKernel\Exception\HttpException;

class HalcashAbancaSender extends Halcash {

    private $abancHalcashDriver;

    function __construct($user, $password, $alias, $url, $abancaHalcashDriver) {
        parent::__construct($user, $password, $alias, $url);
        $this->abancHalcashDriver = $abancaHalcashDriver;
    }

    public function sendV3($phone, $prefix, $amount, $reference, $pin, $transaction_id) {
        $result = $this->abancHalcashDriver->execute($amount, $pin, $phone, "Chip-Chap", $reference);
        if($result->status != "ok") return $result;
        return array("errorcode" => 0, "halcashticket" => $result->data->last_halcash_ticket);
    }

}