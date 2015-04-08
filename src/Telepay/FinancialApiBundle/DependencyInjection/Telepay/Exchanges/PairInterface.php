<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/7/15
 * Time: 1:54 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Exchanges;



interface PairInterface {
    public function getFirst();
    public function getSecond();
}