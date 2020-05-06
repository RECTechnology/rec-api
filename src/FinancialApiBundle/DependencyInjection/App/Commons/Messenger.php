<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:26
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;


interface Messenger {
    function send($msg);
}