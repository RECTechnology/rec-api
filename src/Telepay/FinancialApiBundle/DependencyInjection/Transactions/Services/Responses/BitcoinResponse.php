<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/2/15
 * Time: 1:34 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Services\Responses;


class BitcoinResponse implements \JsonSerializable, \Serializable{

    private $id;
    private $expires_in = 3600;
    private $address;
    private $satoshis;
    private $confirmations;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getSatoshis()
    {
        return $this->satoshis;
    }

    /**
     * @return mixed
     */
    public function getConfirmations()
    {
        return $this->confirmations;
    }

    public function __construct($id, $expires_in, $address, $satoshis, $confirmations){
        $this->id = $id;
        $this->expires_in = $expires_in;
        $this->address = $address;
        $this->satoshis = $satoshis;
        $this->confirmations = $confirmations;
    }

    public function toString(){
        return json_encode($this->jsonSerialize(), true);
    }


    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize(){
        return array(
            'id' => $this->id,
            'expires_in' => $this->expires_in,
            'address' => $this->address,
            'satoshis' => $this->expires_in,
            'confirmations' => $this->expires_in,
        );
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        // TODO: Implement serialize() method.
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
    }
}