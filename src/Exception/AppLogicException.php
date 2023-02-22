<?php


namespace App\Exception;


use RuntimeException;

class AppLogicException extends AppException {

    /**
     * AppLogicException constructor.
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct(400, $message);
    }
}