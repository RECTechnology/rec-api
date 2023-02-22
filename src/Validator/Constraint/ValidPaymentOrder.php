<?php

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Class ValidPaymentOrder
 * @package App\Validator\Constraint
 * @Annotation
 */
class ValidPaymentOrder extends Constraint {

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}