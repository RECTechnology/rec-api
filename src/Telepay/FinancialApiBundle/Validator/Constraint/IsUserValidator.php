<?php

namespace Telepay\FinancialApiBundle\Validator\Constraint;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;

/**
 * Class IsUserValidator
 * @package Telepay\FinancialApiBundle\Validator\Constraint
 */
class IsUserValidator extends ConstraintValidator {

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
        assert($value instanceof User);

        if($value->hasRole("ROLE_COMPANY")) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ string }}' => $value->getId()]
            );
        }
    }
}