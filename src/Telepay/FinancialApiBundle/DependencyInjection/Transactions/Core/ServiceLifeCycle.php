<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 12:28 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface ServiceLifeCycle {
    public function beforeCall();
    public function getTransaction();
    public function afterCall();
}