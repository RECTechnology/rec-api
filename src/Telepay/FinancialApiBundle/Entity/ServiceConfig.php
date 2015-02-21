<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/15/15
 * Time: 5:54 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ServiceConfig {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $service_id;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User", cascade="persist")
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     */
    private $parameters;

    function __construct()
    {
        $this->parameters = json_encode(array());
    }

    /**
     * @return mixed
     */
    public function getParameter($paramName)
    {
        $params = json_decode($this->parameters, true);
        if(!array_key_exists($paramName, $params)) return null;
        return $params[$paramName];
    }

    /**
     * @param mixed $parameters
     */
    public function setParameter($paramName, $paramValue)
    {
        $params = json_decode($this->parameters, true);
        $params[$paramName] = $paramValue;
        $this->parameters = json_encode($params);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getServiceId()
    {
        return $this->service_id;
    }

    /**
     * @param mixed $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param mixed $service_id
     */
    public function setServiceId($service_id)
    {
        $this->service_id = $service_id;
    }

}