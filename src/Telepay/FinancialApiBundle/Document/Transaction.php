<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 25/8/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Interfaces\TransactionTiming;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\TransactionContext;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\TransactionContextInterface;

/**
 * Class Transaction
 * @package Telepay\FinancialApiBundle\Document
 * @MongoDB\Document
 */
class Transaction implements TransactionTiming {

    public static function createFromContext(TransactionContextInterface $context){
        $transaction = new Transaction();
        $transaction->setIp($context->getRequestStack()->getCurrentRequest()->getClientIp());
        $transaction->setTimeIn(new \MongoDate());
        $transaction->setUser($context->getUser()->getId());
        $transaction->setDataIn($context->getRequestStack()->getCurrentRequest());
        return $transaction;
    }

    /**
     * @var
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var
     * @MongoDB\Int
     */
    private $user;

    /**
     * @var
     * @MongoDB\String
     */
    private $service;

    /**
     * @var
     * @MongoDB\String
     */
    private $ip;

    /**
     * @var
     * @MongoDB\Date
     */
    private $timeIn;

    /**
     * @var
     * @MongoDB\Date
     */
    private $timeOut;

    /**
     * @var
     * @MongoDB\String
     */
    private $status;

    /**
     * @var
     * @MongoDB\Int
     */
    private $version;


    /**
     * @var
     * @MongoDB\Hash
     */
    private $dataIn;

    /**
     * @var
     * @MongoDB\Hash
     */
    private $data;

    /**
     * @var
     * @MongoDB\Hash
     */
    private $dataOut;

    /**
     * @var
     * @MongoDB\Hash
     */
    private $debugData;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param int $user
     * @return self
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return int $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set service
     *
     * @param int $service
     * @return self
     */
    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Get service
     *
     * @return int $service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set timeIn
     *
     * @param timestamp $timeIn
     * @return self
     */
    public function setTimeIn($timeIn)
    {
        $this->timeIn = $timeIn;
        return $this;
    }

    /**
     * Get timeIn
     *
     * @return timestamp $timeIn
     */
    public function getTimeIn()
    {
        return $this->timeIn;
    }

    /**
     * Set timeOut
     *
     * @param timestamp $timeOut
     * @return self
     */
    public function setTimeOut($timeOut)
    {
        $this->timeOut = $timeOut;
        return $this;
    }

    /**
     * Get timeOut
     *
     * @return timestamp $timeOut
     */
    public function getTimeOut()
    {
        return $this->timeOut;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getDataIn()
    {
        return $this->dataIn;
    }

    /**
     * @param mixed $dataIn
     */
    public function setDataIn($dataIn)
    {
        $this->dataIn = $dataIn;
    }

    /**
     * @return mixed
     */
    public function getDataOut()
    {
        return $this->dataOut;
    }

    /**
     * @param mixed $dataOut
     */
    public function setDataOut($dataOut)
    {
        $this->dataOut = $dataOut;
    }

    /**
     * @return mixed
     */
    public function getDebugData()
    {
        return $this->debugData;
    }

    /**
     * @param mixed $debugData
     */
    public function setDebugData($debugData)
    {
        $this->debugData = $debugData;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
