<?php

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class HasValidEmail
 * @package App\Validator\Constraint
 * @Annotation
 */
class HasValidEmail extends Constraint {
    public $message = 'The account "{{ string }}" has empty or invalid email.';
}