<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\DependencyInjection\Transactions\Core;


interface CashOutInterface {

    public function send($paymentInfo);
    public function getPayOutStatus($id);
    public function getPayOutInfo($request);
    public function getCurrency();


}