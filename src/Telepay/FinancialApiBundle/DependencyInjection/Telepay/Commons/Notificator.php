<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:26
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;


interface Notificator {
    function msg($msg);
}