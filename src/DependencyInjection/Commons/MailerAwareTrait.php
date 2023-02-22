<?php

namespace App\DependencyInjection\Commons;

use Symfony\Component\Mailer\MailerInterface;

trait MailerAwareTrait
{

    private MailerInterface $mailer;

    /**
     * @required
     * @param MailerInterface $mailer
     * @return void
     */
    public function setMailer(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }
}