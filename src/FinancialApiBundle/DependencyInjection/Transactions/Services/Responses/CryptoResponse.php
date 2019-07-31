<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/2/15
 * Time: 1:34 AM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Services\Responses;


class CryptoResponse implements \JsonSerializable{

    private $id;
    private $expires_in = 3600;
    private $address;
    private $amount;
    private $min_confirmations;

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
    public function getMinConfirmations()
    {
        return $this->min_confirmations;
    }

    public function __construct($id, $expires_in, $address, $amount, $confirmations){
        $this->id = $id;
        $this->expires_in = $expires_in;
        $this->address = $address;
        $this->amount = $amount;
        $this->min_confirmations = $confirmations;
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
            'amount' => $this->amount,
            'min_confirmations' => $this->min_confirmations
        );
    }

}