<?php


namespace App\FinancialApiBundle\Exception;


use RuntimeException;

class AppLogicException extends RuntimeException {

    /**
     * AppLogicException constructor.
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message);
    }
}