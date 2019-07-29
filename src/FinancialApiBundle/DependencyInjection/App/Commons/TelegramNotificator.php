<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:27
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;


class TelegramNotificator implements Notificator {

    private $chatId;
    private $telegramToken;

    /**
     * TelegramNotificator constructor.
     * @param $chatId
     * @param $telegramToken
     */
    public function __construct($chatId, $telegramToken)
    {
        $this->chatId = $chatId;
        $this->telegramToken = $telegramToken;
    }

    function send($msg)
    {
        $ops = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => 'chat_id=' . $this->chatId . '&text=' . $msg
            ]
        ];

       file_get_contents(
            "https://api.telegram.org/bot" . $this->telegramToken . "/sendMessage",
            false,
            stream_context_create($ops)
        );
    }
}