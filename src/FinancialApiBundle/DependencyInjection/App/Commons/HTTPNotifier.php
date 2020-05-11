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
        $content = json_encode(
            $notification->getContent(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $ops = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($content)
                ],
                'content' => $content
            ]
        ];

        $response = @file_get_contents(
            $notification->getUrl(),
            false,
            stream_context_create($ops)
        );

        if(isset($http_response_header)) {
            $status_line = $http_response_header[0];
            preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $matches);
            $status = intval($matches[1]);
            if ($status < 400) $on_success($response);
            else $on_failure($response);
        }
        else $on_failure($response);
        $on_finally($response);
    }
}