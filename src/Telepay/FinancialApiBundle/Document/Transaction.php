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

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;

/**
 * Class Transaction
 * @package Telepay\FinancialApiBundle\Document
 * @MongoDB\Document
 * @ExclusionPolicy("none")
 */
class Transaction implements TransactionTiming {

    /**
     * @Exclude
     */
    public static $STATUS_CREATED = "created";

    /**
     * @Exclude
     */
    public static $STATUS_EXPIRED = "expired";

    /**
     * @Exclude
     */
    public static $STATUS_RECEIVED = "received";

    /**
     * @Exclude
     */
    public static $STATUS_SUCCESS = "success";

    /**
     * @Exclude
     */
    public static $STATUS_FAILED = "failed";

    /**
     * @Exclude
     */
    public static $STATUS_REVIEW = "review";

    /**
     * @Exclude
     */
    public static $STATUS_CANCELLED = "cancelled";

    /**
     * @Exclude
     */
    public static $STATUS_LOCKED = "locked";

    /**
     * @Exclude
     */
    public static $STATUS_RETURNED = "returned";

    /**
     * @Exclude
     */
    public static $STATUS_UNKNOWN = "unknown";

    /**
     * @var
     * @MongoDB\Date
     */
    private $created;

    /**
     * @var
     * @MongoDB\Date
     */
    private $updated;

    public function __construct(){
        $this->created=new \MongoDate();
        $this->updated=new \MongoDate();
    }

    public static function createFromContext(TransactionContextInterface $context){
        $transaction = new Transaction();
        $transaction->setIp($context->getRequestStack()->getCurrentRequest()->getClientIp());
        $transaction->setTimeIn(new \MongoDate());
        $transaction->setUser($context->getUser()->getId());
        $transaction->setDataIn($context->getRequestStack()->getCurrentRequest());
        $transaction->setStatus(Transaction::$STATUS_CREATED);
        return $transaction;
    }

    public static function createFromTransaction(Transaction $trans){
        $transaction = new Transaction();
        $transaction->setStatus('success');
        $transaction->setScale($trans->getScale());
        $transaction->setCurrency($trans->getCurrency());
        $transaction->setIp($trans->getIp());
        $transaction->setTimeIn(new \MongoDate());
        $transaction->setVersion($trans->getVersion());
        $transaction->setService($trans->getService());
        $transaction->setVariableFee($trans->getVariableFee());
        $transaction->setFixedFee($trans->getFixedFee());
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
     * @var
     * @MongoDB\String
     */
    private $currency;

    /**
     * @var
     * @MongoDB\Float
     */
    private $amount;

    /**
     * @var
     * @MongoDB\Float
     */
    private $variableFee;

    /**
     * @var
     * @MongoDB\Int
     */
    private $fixedFee;

    /**
     * @var
     * @MongoDB\float
     */
    private $total;

    /**
     * @var
     * @MongoDB\Int
     */
    private $scale;

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

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getVariableFee()
    {
        return $this->variableFee;
    }

    /**
     * @param mixed $variableFee
     */
    public function setVariableFee($variableFee)
    {
        $this->variableFee = $variableFee;
    }

    /**
     * @return mixed
     */
    public function getFixedFee()
    {
        return $this->fixedFee;
    }

    /**
     * @param mixed $fixedFee
     */
    public function setFixedFee($fixedFee)
    {
        $this->fixedFee = $fixedFee;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param mixed $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return mixed
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param mixed $scale
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param mixed $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }
}
