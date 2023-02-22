<?php

namespace App\Exception;

/**
 * Class AttemptToChangeStatusException
 * @package App\Exception
 */
class AttemptToChangeStatusException extends AppException {

    /**
     * AttemptToChangeStatusException constructor.
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct(400, $message);
    }
}