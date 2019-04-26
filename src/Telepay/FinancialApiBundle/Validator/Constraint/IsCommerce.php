<?php

namespace Telepay\FinancialApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class AccountIsCommerce
 * @package Telepay\FinancialApiBundle\Validator\Constraint
 * @Annotation
 */
class IsCommerce extends Constraint {
    public $message = 'The account "{{ string }}" is not a commerce: account is required to be a commerce.';
}