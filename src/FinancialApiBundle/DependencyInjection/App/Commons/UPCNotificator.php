<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:27
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;


use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UPCNotificator implements Notificator {

    /** @var ParameterBagInterface */
    private $bag;

    /** @var LoggerInterface */
    private $logger;

    /**
     * UPCNotificator constructor.
     * @param ParameterBagInterface $bag
     * @param LoggerInterface $logger
     */
    public function __construct(ParameterBagInterface $bag, LoggerInterface $logger)
    {
        $this->bag = $bag;
        $this->logger = $logger;
    }

    function send($msg) {
        $url = $this->bag->get('bcn_notification_url');
        $us = $this->bag->get('bcn_notification_username');
        $pw = $this->bag->get('bcn_notification_password');

        $auth = base64_encode($us . ":" . $pw);
        $ops = [
            'http' => [
                'method' => 'POST',
                'header' => ['Content-Type: application/json','Authorization: Basic ' . $auth],
                'content' => $msg
            ]
        ];

        $this->logger->debug("UPC REQUEST: " . $msg);
        $resp = file_get_contents(
            $url,
            false,
            stream_context_create($ops)
        );
        $this->logger->debug("UPC RESPONSE: $resp");
        return $resp;
    }
}