<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 5/29/15
 * Time: 1:57 AM
 */

namespace Telepay\FinancialApiBundle\Financial;

interface WayInterface {
    public function getStartNode();
    public function getEndNode();
    public function getMinAmount();
}