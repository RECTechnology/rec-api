<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\PaynetGetBarcode;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\PaynetGetStatus;


class PaynetReferenceService extends BaseService{

    public function getPaynetGetBarcode(){

        return new PaynetGetBarcode();

    }

    public function getPaynetGetStatus(){

        return new PaynetGetStatus();

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