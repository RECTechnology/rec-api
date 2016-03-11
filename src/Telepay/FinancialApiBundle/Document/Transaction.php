<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 25/8/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\HttpFoundation\Request;
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
    public static $STATUS_ERROR = "error";

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
     * @Exclude
     */
    public static $TYPE_IN = "in";

    /**
     * @Exclude
     */
    public static $TYPE_OUT = "out";

    /**
     * @Exclude
     */
    public static $TYPE_SWIFT = "swift";

    /**
     * @Exclude
     */
    public static $TYPE_FEE = "fee";

    /**
     * @Exclude
     */
    public static $TYPE_EXCHANGE = "exchange";

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
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }

    public static function createFromRequest(Request $request){
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setStatus(Transaction::$STATUS_CREATED);
        $transaction->setNotificationTries(0);
        $transaction->setMaxNotificationTries(3);
        $transaction->setNotified(false);
        return $transaction;
    }

    public static function createFromTransaction(Transaction $trans){
        $transaction = new Transaction();
        $transaction->setStatus('success');
        $transaction->setScale($trans->getScale());
        $transaction->setCurrency($trans->getCurrency());
        $transaction->setIp($trans->getIp());
        $transaction->setVersion($trans->getVersion());
        $transaction->setService($trans->getService());
        $transaction->setMethod($trans->getMethod());
        $transaction->setType($trans->getType());
        $transaction->setVariableFee($trans->getVariableFee());
        $transaction->setFixedFee($trans->getFixedFee());
        $transaction->setUser($trans->getUser());
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
    private $method;

    /**
     * @var
     * @MongoDB\String
     */
    private $posId;

    /**
     * @var
     * @MongoDB\String
     */
    private $ip;

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
     * @var
     * @MongoDB\Int
     */
    private $max_notification_tries;

    /**
     * @var
     * @MongoDB\Int
     */
    private $notification_tries;

    /**
     * @var
     * @MongoDB\Boolean
     */
    private $notified;

    /**
     * @var
     * @MongoDB\Hash
     */
    private $pay_in_info;

    /**
     * @var
     * @MongoDB\Hash
     */
    private $pay_out_info;

    /**
     * @var
     * @MongoDB\String
     */
    private $method_in;

    /**
     * @var
     * @MongoDB\String
     */
    private $method_out;

    /**
     * @var
     * @MongoDB\String
     */
    private $type;

    /**
     * @var
     * @MongoDB\Int
     */
    private $price;

    /**
     * @var
     * @MongoDB\Int
     */
    private $client;

    /**
     * @var
     * @MongoDB\Date
     */
    private $last_price_at;

    /**
     * @var
     * @MongoDB\Date
     */
    private $last_check;

    /**
     */
    private $client_data = array();

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

    /**
     * @return mixed
     */
    public function getMaxNotificationTries()
    {
        return $this->max_notification_tries;
    }

    /**
     * @param mixed $max_notification_tries
     */
    public function setMaxNotificationTries($max_notification_tries)
    {
        $this->max_notification_tries = $max_notification_tries;
    }

    /**
     * @return mixed
     */
    public function getNotificationTries()
    {
        return $this->notification_tries;
    }

    /**
     * @param mixed $notification_tries
     */
    public function setNotificationTries($notification_tries)
    {
        $this->notification_tries = $notification_tries;
    }

    /**
     * @return mixed
     */
    public function getNotified()
    {
        return $this->notified;
    }

    /**
     * @param mixed $notified
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;
    }

    /**
     * @return mixed
     */
    public function getPosId()
    {
        return $this->posId;
    }

    /**
     * @param mixed $posId
     */
    public function setPosId($posId)
    {
        $this->posId = $posId;
    }

    /**
     * @return mixed
     */
    public function getPayInInfo()
    {
        return $this->pay_in_info;
    }

    /**
     * @param mixed $pay_in_info
     */
    public function setPayInInfo($pay_in_info)
    {
        $this->pay_in_info = $pay_in_info;
    }

    /**
     * @return mixed
     */
    public function getPayOutInfo()
    {
        return $this->pay_out_info;
    }

    /**
     * @param mixed $pay_out_info
     */
    public function setPayOutInfo($pay_out_info)
    {
        $this->pay_out_info = $pay_out_info;
    }

    /**
     * @return mixed
     */
    public function getMethodIn()
    {
        return $this->method_in;
    }

    /**
     * @param mixed $method_in
     */
    public function setMethodIn($method_in)
    {
        $this->method_in = $method_in;
    }

    /**
     * @return mixed
     */
    public function getMethodOut()
    {
        return $this->method_out;
    }

    /**
     * @param mixed $method_out
     */
    public function setMethodOut($method_out)
    {
        $this->method_out = $method_out;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getClientData()
    {
        return $this->client_data;
    }

    /**
     * @param mixed $client_data
     */
    public function setClientData($client_data)
    {
        $this->client_data = $client_data;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return mixed
     */
    public function getLastPriceAt()
    {
        return $this->last_price_at;
    }

    /**
     * @param mixed $last_price_at
     */
    public function setLastPriceAt($last_price_at)
    {
        $this->last_price_at = $last_price_at;
    }

    /**
     * @return mixed
     */
    public function getLastCheck()
    {
        return $this->last_check;
    }

    /**
     * @param mixed $last_check
     */
    public function setLastCheck($last_check)
    {
        $this->last_check = $last_check;
    }
}
