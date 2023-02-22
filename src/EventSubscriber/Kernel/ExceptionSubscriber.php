<?php

namespace App\EventSubscriber\Kernel;
use App\Exception\AppException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{

    public function __construct(){
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'parseException'
        ];
    }

    public function parseException(ExceptionEvent $event) {

        $exception = $event->getThrowable();
        $data = [
            'message' => $exception->getMessage()
        ];

        if ($exception instanceof HttpException) {
            $data['status'] = 'error';
            $code = $exception->getStatusCode();
            if ($exception instanceof AppException) {
                if($exception->data){
                    if($exception->data instanceof ConstraintViolationListInterface) {
                        $data['errors'] = [];
                        foreach ($exception->data as $violation){
                            $data['errors'] []= [
                                'property' => $violation->getPropertyPath(),
                                'message' => $violation->getMessage()
                            ];
                        }
                    }
                    elseif (is_array($exception->data)){
                        $data['errors'] = [$exception->data];
                    }
                }

            }

        }
        else {
            $data['status'] = 'critical';
            $code = 500;
        }

        $event->setResponse(new JsonResponse($data, $code));
    }
}