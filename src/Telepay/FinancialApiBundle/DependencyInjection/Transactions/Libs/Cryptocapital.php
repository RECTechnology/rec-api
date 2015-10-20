<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class Cryptocapital{

    function __construct()
    {
    }

    public function request($currency, $amount, $narrative, $description, $id){

        $position = __DIR__;
        $amount = number_format((float)$amount/100, 2, '.', '');
//        $process = new Process('nodejs '.$position.'/nodejs/transfer.js -c '.$currency.' -a '.$amount.' -n "'.$narrative.','.$description.'-'.$id.'"');
        $process = new Process('whoami');
//        we can up the timeout for security
        $process->setTimeout(160);

        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $response = $process->getOutput();
die(print_r($response,true));
        $response = json_decode($response,true);

//        ******* ERROR *****
//        "apiVersion": 1,
//        "key": "1AUTwMzqehYZVqKTvdkVwat4knMzMSkhYU",
//        "nonce": 1444980212382,
//        "rcpt": "1N4jpCBofW546gEsrxrNs4UnUUP8QNo7c7",
//        "params": {
//            "nonce": 1444979870182,
//            "msg": "Insufficient funds"
//        },
//        "signed": "GzMiNLbgUjsqS1StFMqBJcFgHVapNF8L0Xv+Nc29AjwGLV9PU/g7Hn5Sty7Dn+aYqGh8jIQLHdjkkwlIct2jcZM="

//        ***** SUCCESS ******
//        {
//            "apiVersion":1,
//            "key":"1AUTwMzqehYZVqKTvdkVwat4knMzMSkhYU",
//            "nonce":1444980535846,
//            "rcpt":"1N4jpCBofW546gEsrxrNs4UnUUP8QNo7c7",
//            "params":{
//                "id":"10352",
//                "date":"2015-10-16",
//                "sendAccount":"9120241702",
//                "receiveAccount":"9120274348",
//                "sendCurrency":"EUR",
//                "receiveCurrency":"EUR",
//                "sendAmount":"10.00",
//                "receiveAmount":"10.00",
//                "narrative":"pere@chip-chap.com, pere - test"
//            },
//            "signed":"G9leCyJjTXepCOiaNsRFwzF+kGSIsHCnRMunk148tBrAA3OIrDRXVPJJ9vJBV5PrxiixpgZFpusxPflsr6u+jbY="
//        }



        return $response;

    }


}
