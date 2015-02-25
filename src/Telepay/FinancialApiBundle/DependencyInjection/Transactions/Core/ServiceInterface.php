<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface ServiceInterface {
    public function getName();
    public function getRole();
    public function getCname();
    public function getBase64Image();
}