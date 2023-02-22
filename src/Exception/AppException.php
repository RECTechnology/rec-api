<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AppException
 * @package App\Exception
 */
class AppException extends HttpException {
    public $data;

    /**
     * AppException constructor.
     * @param int $statusCode
     * @param string $message
     * @param mixed $data
     */
    public function __construct(int $statusCode, string $message, $data = null)
    {
        parent::__construct($statusCode, $message);
        $this->data = $data;
    }

}