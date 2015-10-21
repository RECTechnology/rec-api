<?php

namespace Telepay\FinancialApiBundle\Financial\Driver;

class RuralviaDriver {

    private $ruralviaBot;

    public function __construct(PhantomBotExecutor $ruralviaBot){
        $this->ruralviaBot = $ruralviaBot;
    }






}