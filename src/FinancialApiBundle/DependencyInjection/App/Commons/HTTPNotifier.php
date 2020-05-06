<?php


namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\Notification;

/**
 * Class HTTPNotifier
 * @package App\FinancialApiBundle\DependencyInjection\App\Commons
 */
class HTTPNotifier implements Notifier {

    /**
     * @param Notification $notification
     * @param callable $on_success
     * @param callable $on_failure
     * @param callable $on_finally
     */
    function send(Notification $notification, $on_success, $on_failure, $on_finally): void {
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
        if($response) $on_success($response);
        else $on_failure($response);
        $on_finally($response);
    }
}