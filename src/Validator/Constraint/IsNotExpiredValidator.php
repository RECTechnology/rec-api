<?php

namespace App\Validator\Constraint;

use DateTime;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\Group;


/**
 * Class IsNotExpiredValidator
 * @package App\Validator\Constraint
 */
class IsNotExpiredValidator extends ConstraintValidator {

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @throws \Exception
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        if($value !== null){
            $expiry_date = explode("/", $value);
            if(count($expiry_date) !== 2)
                $expiry_date = explode("-", $value);

            assert(count($expiry_date) === 2);

            $expiry_month = intval($expiry_date[0]);
            $expiry_year = intval(strlen($expiry_date[1]) == 2? "20" . $expiry_date[1]: $expiry_date[1]);
            if($expiry_year > intval(date('Y'))) return;
            if($expiry_year < intval(date('Y')) or $expiry_month < intval(date('m'))) {
                $this->context->addViolation(
                    $constraint->message
                );
            }
        }
    }
}