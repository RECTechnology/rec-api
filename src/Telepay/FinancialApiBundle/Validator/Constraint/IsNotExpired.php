<?php

namespace Telepay\FinancialApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class AccountIsNotExpired
 * @package Telepay\FinancialApiBundle\Validator\Constraint
 * @Annotation
 */
class IsNotExpired extends Constraint {
    public $message = 'The card is expired!.';

}