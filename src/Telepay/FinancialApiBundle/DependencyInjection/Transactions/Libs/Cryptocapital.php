<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class Cryptocapital{

    function __construct()
    {
    }

    public function request(){

        $position = __DIR__;

        $process = new Process('nodejs '.$position.'/nodejs/sample.js');
        $process->run();

// executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $response = $process->getOutput();

        return $response;

    }

    function getReference(){
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $array_chars = str_split($chars);
        shuffle($array_chars);

        return substr(implode("", $array_chars),0,5);
    }

}
