<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\UkashRedirect;

class UkashTPVService extends BaseService{

    private $mode;

    public function getUkash($mode){

        $this->mode=$mode;
        return new UkashRedirect($mode);
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