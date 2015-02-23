<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 1:15 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;



interface TransactionContext{
    public function getRequest();
    public function getUser();
    public function getODM();
    public function getORM();
}

