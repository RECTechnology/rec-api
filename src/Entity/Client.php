<?php
// src/App/FinancialApiBundle/Entity/Client.php

namespace App\Entity;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Entity
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name='client';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     * @Exclude
     */
    private $group;

    /**
     * @Expose
     */
    private $group_data = array();

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $cname;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @param mixed $group_data
     */
    public function setGroupData($group_data)
    {
        $this->group_data = $group_data;
    }

    /**
     * @return mixed
     */
    public function getCname()
    {
        return $this->cname;
    }

    /**
     * @param mixed $cname
     */
    public function setCname($cname)
    {
        $this->cname = $cname;
    }

}