<?php

namespace App\FinancialApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class AccountIsNotExpired
 * @package App\FinancialApiBundle\Validator\Constraint
 * @Annotation
 */
class IsNotExpired extends Constraint {
    public $message = 'Card is expired: card cannot be expired at this time.';

}