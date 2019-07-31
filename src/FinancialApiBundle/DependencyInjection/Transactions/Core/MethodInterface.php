<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;


interface MethodInterface {
    public function getName();
    public function getCname();
    public function getType();
    public function getCurrency();
    public function getBase64Image();
}