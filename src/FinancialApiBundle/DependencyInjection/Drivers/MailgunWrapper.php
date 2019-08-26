<?php

namespace App\FinancialApiBundle\DependencyInjection\Drivers;


use App\FinancialApiBundle\DependencyInjection\Transactions\Core\ContainerAwareInterface;
use Mailgun\Mailgun;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class MailgunWrapper {

    use ContainerAwareTrait;

    public function send($from, $to, $subject, $content = "", $attachments = []){
        $endpoint = $this->container->getParameter('mailgun_endpoint');
        $key = $this->container->getParameter('mailgun_key');
        $domain = $this->container->getParameter('mailgun_domain');

        $mg = Mailgun::create($key, $endpoint); // For EU servers

        $mg->messages()->send(
                $domain,
            [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'text'    => $content,
                'attachments' => $attachments
            ]
        );
    }

}