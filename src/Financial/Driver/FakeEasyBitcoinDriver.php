<?php
/*
EasyBitcoin-PHP

A simple class for making calls to Bitcoin's API using PHP.
https://github.com/aceat64/EasyBitcoin-PHP

====================

The MIT License (MIT)

Copyright (c) 2013 Andrew LeCody

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

====================

// Initialize Bitcoin connection/object
$bitcoin = new Bitcoin('username','password');

// Optionally, you can specify a host and port.
$bitcoin = new Bitcoin('username','password','host','port');
// Defaults are:
//	host = localhost
//	port = 8332
//	proto = http

// If you wish to make an SSL connection you can set an optional CA certificate or leave blank
// This will set the protocol to HTTPS and some CURL flags
$bitcoin->setSSL('/full/path/to/mycertificate.cert');

// Make calls to bitcoind as methods for your object. Responses are returned as an array.
// Examples:
$bitcoin->getinfo();
$bitcoin->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
$bitcoin->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

// The full response (not usually needed) is stored in $this->response while the raw JSON is stored in $this->raw_response

// When a call fails for any reason, it will return FALSE and put the error message in $this->error
// Example:
echo $bitcoin->error;

// The HTTP status code can be found in $this->status and will either be a valid HTTP status code or will be 0 if cURL was unable to connect.
// Example:
echo $bitcoin->status;

*/

namespace App\Financial\Driver;


use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use StephenHill\Base58;

class FakeEasyBitcoinDriver {

    private EntityManagerInterface $em;
    private string $prefix;

    /**
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $em){
        $this->em = $em;
        $this->prefix = $container->getParameter('blockchain_address_prefix');
    }

    /**
     * @return string
     * @throws \Exception
     */
    function getnewaddress() {
        $base58 = new Base58();
        return $this->prefix . $base58->encode(random_bytes(16));
    }

    /**
     * @param $account
     * @return string
     * @throws \Exception
     */
    function getaccountaddress($account){
        return $this->getnewaddress();
    }

    function validateaddress($address){
        $accountRepo = $this->em->getRepository("FinancialApiBundle:Group");
        $account = $accountRepo->findBy(['rec_address' => $address]);
        return (bool) $account;
    }
}
