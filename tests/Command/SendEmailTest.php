<?php

namespace App\Tests\Command;

use App\Tests\BaseApiTest;

class SendEmailTest extends BaseApiTest
{

    function testSendSampleEmail(){
        $this->markTestIncomplete("For now, all emails are sent synchronously, to make it asynchronous see https://symfony.com/doc/4.4/mailer.html#sending-messages-async");
        $output = $this->runCommand(
            "swiftmailer:email:send",
            [
                '--mailer' => 'first_mailer',
                '--from' => 'sender@example.com',
                '--to' => 'receiver@example.com',
                '--subject' => 'This is a subject',
                '--body' => 'This is the body'
            ]
        );
        self::assertNotEmpty($output);
    }

}
