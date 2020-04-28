<?php


namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\Notification;

/**
 * Class PaymentOrderNotificator
 * @package App\FinancialApiBundle\DependencyInjection\App\Commons
 */
class HTTPNotificator implements Notificator {

    function send(Notification $notification, $success, $failure) {
        $ops = [
            'http' => [
                'method' => 'POST',
                'header' => ['Content-Type: application/json'],
                'content' => $notification->getContent()
            ]
        ];

        $response = file_get_contents(
            $notification->getUrl(),
            false,
            stream_context_create($ops)
        );
        if($response) $success($response);
        else $failure($response);
    }
}