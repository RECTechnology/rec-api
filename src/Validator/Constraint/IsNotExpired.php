<?php

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class AccountIsNotExpired
 * @package App\Validator\Constraint
 * @Annotation
 */
class IsNotExpired extends Constraint {
    public $message = 'Card is expired: card cannot be expired at this time.';

}