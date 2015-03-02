<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 1:20 AM
 */


namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Services;


use Symfony\Component\HttpFoundation\Request;

interface Bitcoin{
    public function payment(Request $request, $mode, $id);
}