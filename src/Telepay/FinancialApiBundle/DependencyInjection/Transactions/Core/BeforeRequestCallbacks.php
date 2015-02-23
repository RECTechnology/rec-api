<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 1:45 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface BeforeRequestCallbacks{
    public function getSentData();
    public function getStartTime();
}