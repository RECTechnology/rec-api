<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/1/15
 * Time: 1:53 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Driver;

use Telepay\FinancialApiBundle\Financial\ExecutorInterface;

class PhantomBotExecutor implements ExecutorInterface {

    private $script;
    private $env;
    private $arguments;

    public function __construct($script, $env, array $arguments){
        $this->script = $script;
        $this->env = $env;
        $this->arguments = $arguments;
    }


    public function execute(){
        //exec("if ! test -x ")
        $command = "cd '$this->env' && phantomjs '$this->script'";
        foreach($this->arguments as $argument){
            $command .= " " . "'$argument'";
        }
        $output = array();
        $retval = 0;
        exec($command, $output, $retval);
        if($retval != 0) throw new \LogicException("Phantom Bot crashed at step " . $retval);
        return $output;
    }
}