<?php

namespace App\FinancialApiBundle\Exception;

/**
 * Class ExceptionController
 * @package App\FinancialApiBundle\Exception
 */
class ExceptionController extends \FOS\RestBundle\Controller\ExceptionController {

    protected function createExceptionWrapper(array $parameters) {

        $exception = $parameters['exception'];

        if ($exception->getClass() == AppException::class) {
            $encapsulated = json_decode($exception->getMessage(), true);
            if(isset($encapsulated['errors']))
                return new ExceptionWrapper(
                    $parameters['status'],
                    $parameters['status_text'],
                    $encapsulated['message'],
                    $encapsulated['errors']
                );
            return new ExceptionWrapper(
                $parameters['status'],
                $parameters['status_text'],
                $encapsulated['message']
            );
        }
        return new ExceptionWrapper(
            $parameters['status'],
            $parameters['status_text'],
            $parameters['message']
        );
    }

}
