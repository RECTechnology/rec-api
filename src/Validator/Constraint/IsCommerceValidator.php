<?php

namespace App\Validator\Constraint;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Group;

/**
 * Class IsCommerceValidator
 * @package App\Validator\Constraint
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
        assert($value instanceof Group);

        if($value->getType() !== "COMPANY") {
            $this->context->addViolation(
                $constraint->message,
                ['{{ string }}' => $value->getId()]
            );
        }
    }
}