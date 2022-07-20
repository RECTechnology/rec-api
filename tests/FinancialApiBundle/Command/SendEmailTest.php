<?php

namespace Test\FinancialApiBundle\Command;

use Test\FinancialApiBundle\BaseApiTest;

class SendEmailTest extends BaseApiTest
{

    function testSendSampleEmail(){
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
