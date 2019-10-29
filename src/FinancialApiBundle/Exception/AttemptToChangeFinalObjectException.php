<?php

namespace App\FinancialApiBundle\Exception;

/**
 * Class AttemptToChangeFinalObjectException
 * @package App\FinancialApiBundle\Exception
 */
class AttemptToChangeFinalObjectException extends PreconditionFailedException {

    /**
     * AttemptToChangeFinalObjectException constructor.
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message);
    }
}