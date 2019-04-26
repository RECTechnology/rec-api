<?php

namespace Telepay\FinancialApiBundle\Validator\Constraint;

use DateTime;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Telepay\FinancialApiBundle\Entity\Group;


/**
 * Class IsNotExpiredValidator
 * @package Telepay\FinancialApiBundle\Validator\Constraint
 */
class IsNotExpiredValidator extends ConstraintValidator {

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        $expiry_date = DateTime::createFromFormat('d-m-y', '1-'.$value);
        $expiry_date->setTime(00, 00, 00);
        /** @var Group $value */
        if($expiry_date < new DateTime()) {
            $this->context->addViolation(
                $constraint->message
            );
        }
    }
}