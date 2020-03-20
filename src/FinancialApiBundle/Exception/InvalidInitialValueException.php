<?php

namespace App\FinancialApiBundle\Exception;

/**
 * Class InvalidInitialValueException
 * @package App\FinancialApiBundle\Exception
 */
class InvalidInitialValueException extends PreconditionFailedException {

    /**
     * InvalidInitialValueException constructor.
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message);
    }
}