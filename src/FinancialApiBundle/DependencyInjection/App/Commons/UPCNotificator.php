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


/**
 * Class UPCNotificator
 * @package App\FinancialApiBundle\DependencyInjection\App\Commons
 */
class UPCNotificator implements Notificator {

    /** @var ParameterBagInterface */
    private $parameters;

    /** @var LoggerInterface */
    private $logger;

    /**
     * UPCNotificator constructor.
     * @param ParameterBagInterface $parameters
     * @param LoggerInterface $logger
     */
    public function __construct(ParameterBagInterface $parameters, LoggerInterface $logger)
    {
        $this->parameters = $parameters;
        $this->logger = $logger;
    }

    function send($msg) {
        $url = $this->parameters->get('bcn_notification_url');
        $us = $this->parameters->get('bcn_notification_username');
        $pw = $this->parameters->get('bcn_notification_password');
        $this->logger->debug("UPC REQUEST URL: " . $url);

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