<?php

namespace App\Exception;

/**
 * Class AttemptToChangeFinalObjectException
 * @package App\Exception
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