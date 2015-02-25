<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\PaysafecardPayment;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;

class PaySafeCardService extends BaseService{

    //This parameters are unique for us. Don't give to the client

    private $username='psc_telepay_test';
    private $password='bJnAWBTUlAelk';

    public function getPaysafecard(){

        return new PaysafecardPayment($this->username,$this->password);

    }

    public function getReceivedData()
    {
        // TODO: Implement getReceivedData() method.
    }

    public function getStatus()
    {
        // TODO: Implement getStatus() method.
    }

    public function getSentData()
    {
        // TODO: Implement getSentData() method.
    }
}