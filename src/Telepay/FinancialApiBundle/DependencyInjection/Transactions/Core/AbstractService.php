<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:44 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


use Telepay\FinancialApiBundle\Document\Transaction;

abstract class AbstractService implements ServiceInterface, \Serializable {

    /**
     * @var string name
     */
    private $name;
    /**
     * @var string role
     */
    private $role;
    /**
     * @var string cname
     */
    private $cname;
    /**
     * @var string base64_image
     */
    private $base64_image;


    function __construct($name, $cname, $role, $base64_image)
    {
        $this->name = $name;
        $this->role = $role;
        $this->cname = $cname;
        $this->base64_image = $base64_image;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getCname()
    {
        return $this->cname;
    }

    /**
     * @return string
     */
    public function getBase64Image()
    {
        return $this->base64_image;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize($array = array()) {
        $array['name'] = $this->name;
        $array['cname'] = $this->cname;
        $array['role'] = $this->role;
        $array['base64_image'] = $this->base64_image;
        return serialize($array);
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
    public function unserialize($serialized) {
        $datas = unserialize($serialized);
        $this->name = $datas['name'];
        $this->cname = $datas['cname'];
        $this->role = $datas['role'];
        $this->name = $datas['base64_image'];
    }

}