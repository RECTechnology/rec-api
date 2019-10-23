<?php

namespace App\FinancialApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class HasValidEmail
 * @package App\FinancialApiBundle\Validator\Constraint
 * @Annotation
 */
class HasValidEmail extends Constraint {
    public $message = 'The account "{{ string }}" has empty or invalid email.';
}