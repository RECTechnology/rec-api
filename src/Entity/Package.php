<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Package
 * @package App\Entity
 * @ORM\Entity
 */
class Package extends AppObject
{
    public function __construct(){
        $this->settings = new ArrayCollection();
    }
    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"admin"})
     */
    private $purchased;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ConfigurationSetting", mappedBy="package")
     * @Serializer\Groups({"admin"})
     */
    private $settings;

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
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPurchased()
    {
        return $this->purchased;
    }

    /**
     * @param mixed $purchased
     */
    public function setPurchased($purchased): void
    {
        $this->purchased = $purchased;
    }

    /**
     * @return ArrayCollection
     */
    public function getSettings(): ArrayCollection
    {
        return $this->settings;
    }

}