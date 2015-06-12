<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/29/15
 * Time: 1:57 AM
 */

namespace Telepay\FinancialApiBundle\Financial;

interface TraderInterface {
    public function buy($amount);
    public function sell($amount);
}