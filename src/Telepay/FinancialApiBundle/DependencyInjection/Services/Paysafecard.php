<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Services;

use Telepay\FinancialApiBundle\DependencyInjection\Services\Libs\PaysafecardPayment;

class Paysafecard{

    //This parameters are unique for us. Don't give to the client

    private $username='psc_telepay_test';
    private $password='bJnAWBTUlAelk';

    public function getPaysafecard(){

        return new PaysafecardPayment($this->username,$this->password);

    }

}