<?php

namespace App\FinancialApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class AppException
 * @package App\FinancialApiBundle\Exception
 */
class AppException extends HttpException {

    /**
     * AppException constructor.
     * @param int $statusCode
     * @param string $message
     * @param ConstraintViolationListInterface|null $violations
     */
    public function __construct(int $statusCode, string $message, ConstraintViolationListInterface $violations = null)
    {
        $message = ['message' => $message];
        if($violations){
            $message['errors'] = [];
            foreach ($violations as $violation){
                $message['errors'] []= [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }
        }

        parent::__construct($statusCode, json_encode($message));
    }
}