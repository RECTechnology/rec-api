<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 1:42 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface AfterRequestCallbacks{
    public function getReceivedData();
    public function getEndTime();
    public function getStatus();

}