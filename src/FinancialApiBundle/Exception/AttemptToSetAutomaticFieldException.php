<?php

namespace App\FinancialApiBundle\Exception;

/**
 * Class AttemptToSetAutomaticFieldException
 * @package App\FinancialApiBundle\Exception
 */
class AttemptToSetAutomaticFieldException extends PreconditionFailedException {

    /**
     * AttemptToSetAutomaticFieldException constructor.
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message);
    }
}