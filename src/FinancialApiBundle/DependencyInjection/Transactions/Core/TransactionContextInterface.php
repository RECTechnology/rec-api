<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 1:15 AM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface TransactionContextInterface extends ContainerAwareInterface{
    public function getRequestStack();
    public function getEnvironment();
    public function getUser();
    public function getODM();
    public function getORM();
}

