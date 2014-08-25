<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

class Transaction {

    private $id;

    private $user;

    private $service;

    private $timeIn;

    private $timeOut;

    private $sentData;

    private $receivedData;

    private $completed;

    private $successful;

}