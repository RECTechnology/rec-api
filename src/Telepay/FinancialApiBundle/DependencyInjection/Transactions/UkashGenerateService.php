<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\UkashBarcode;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;

class UkashGenerateService extends BaseService{

    public function getUkashOnline($mode){

        return new UkashBarcode($mode);

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