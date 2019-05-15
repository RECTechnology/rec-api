<?php

namespace Telepay\FinancialApiBundle\Validator\Constraint;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Telepay\FinancialApiBundle\Entity\Group;

/**
 * Class IsCommerceValidator
 * @package Telepay\FinancialApiBundle\Validator\Constraint
 */
class IsCommerceValidator extends ConstraintValidator {

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
        return;
        assert($value instanceof Group);

        if($value->getType() !== "COMPANY") {
            $this->context->addViolation(
                $constraint->message,
                ['{{ string }}' => $value->getId()]
            );
        }
    }
}