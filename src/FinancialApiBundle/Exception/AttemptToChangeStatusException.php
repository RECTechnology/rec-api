<?php

namespace App\FinancialApiBundle\Exception;

/**
 * Class AttemptToChangeStatusException
 * @package App\FinancialApiBundle\Exception
 */
class AttemptToChangeStatusException extends AppLogicException {

    /**
     * AttemptToChangeStatusException constructor.
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message);
    }
}