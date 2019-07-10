<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:27
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;


class BcnNotificator implements Notificator {

    private $url;
    private $username;
    private $password;

    /**
     * BcnNotificator constructor.
     * @param $url
     * @param $username
     * @param $password
     */
    public function __construct($url , $username, $password)
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
    }

    function msg($msg)
    {
        $ops = [
            'http' => [
                'method' => 'POST',
                'header' => ['Content-Type: application/json','Authorization: Basic ' . base64_encode("$this->username:$this->password")],
                'content' => $msg
            ]
        ];

        return file_get_contents(
            "" .$this->url,
            false,
            stream_context_create($ops)
        );
    }
}