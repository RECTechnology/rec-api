<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 25/8/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Class Transaction
 * @package Telepay\FinancialApiBundle\Document
 * @MongoDB\Document
 */
class Transaction {

    public function __construct(){
        $this->completed = false;
        $this->successful = false;
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
     * @MongoDB\Int
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
    private $sentData;

    /**
     * @var
     * @MongoDB\String
     */
    private $receivedData;

    /**
     * @var
     * @MongoDB\Boolean
     */
    private $mode;

    /**
     * @var
     * @MongoDB\Boolean
     */
    private $completed;

    /**
     * @var
     * @MongoDB\Boolean
     */
    private $successful;

    /**
     * @var
     * @MongoDB\String
     */
    private $status;


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
     * Set sentData
     *
     * @param string $sentData
     * @return self
     */
    public function setSentData($sentData)
    {
        $this->sentData = $sentData;
        return $this;
    }

    /**
     * Get sentData
     *
     * @return string $sentData
     */
    public function getSentData()
    {
        return $this->sentData;
    }

    /**
     * Set receivedData
     *
     * @param string $receivedData
     * @return self
     */
    public function setReceivedData($receivedData)
    {
        $this->receivedData = $receivedData;
        return $this;
    }

    /**
     * Get receivedData
     *
     * @return string $receivedData
     */
    public function getReceivedData()
    {
        return $this->receivedData;
    }

    /**
     * Set mode
     *
     * @param boolean $mode
     * @return self
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get mode
     *
     * @return boolean $mode
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set completed
     *
     * @param boolean $completed
     * @return self
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
        return $this;
    }

    /**
     * Get completed
     *
     * @return boolean $completed
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * Set successful
     *
     * @param boolean $successful
     * @return self
     */
    public function setSuccessful($successful)
    {
        $this->successful = $successful;
        return $this;
    }

    /**
     * Get successful
     *
     * @return boolean $successful
     */
    public function getSuccessful()
    {
        return $this->successful;
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
}
