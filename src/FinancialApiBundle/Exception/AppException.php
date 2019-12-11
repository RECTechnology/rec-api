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
     * @param mixed $data
     */
    public function __construct(int $statusCode, string $message, $data = null)
    {
        $message = ['message' => $message];
        if($data){
            if($data instanceof ConstraintViolationListInterface) {
                $message['errors'] = [];
                foreach ($data as $violation){
                    $message['errors'] []= [
                        'property' => $violation->getPropertyPath(),
                        'message' => $violation->getMessage()
                    ];
                }
            }
            elseif (is_array($data)){
                $message['errors'] = [$data];
            }
        }

        parent::__construct($statusCode, json_encode($message));
    }
}