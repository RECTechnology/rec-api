<?php

namespace Telepay\FinancialApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class AccountIsUser
 * @package Telepay\FinancialApiBundle\Validator\Constraint
 * @Annotation
 */
class IsUser extends Constraint {
    public $message = 'The account "{{ string }}" is a commerce: account is required to be a user.';

}