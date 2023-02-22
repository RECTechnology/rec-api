<?php

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotBlankValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotNullValidator;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Group;

/**
 * Class HasValidEmailValidator
 * @package App\Validator\Constraint
 */
class HasValidEmailValidator extends ConstraintValidator {

    /**
     * Checks if the passed account has a valid email property.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        assert($value instanceof Group);
        $validators = [
            ['validator' => new EmailValidator(), 'constraint' => new Email()],
            ['validator' => new NotBlankValidator(), 'constraint' => new NotBlank()],
            ['validator' => new NotNullValidator(), 'constraint' => new NotNull()],
        ];
        try {
            foreach ($validators as $bundle) {
                $bundle['validator']->validate($value->getEmail(), $bundle['constraint']);
            }
        } catch (\Throwable $e) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ string }}' => $value->getId()]
            );
        }
    }
}