<?php

namespace App\FinancialApiBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Award
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Award extends AppObject
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"public"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"public"})
     */
    private $name_es;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"public"})
     */
    private $name_ca;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    private $golden_threshold;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    private $silver_threshold;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    private $bronze_threshold;

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
    public function getNameEs()
    {
        return $this->name_es;
    }

    /**
     * @param mixed $name_es
     */
    public function setNameEs($name_es): void
    {
        $this->name_es = $name_es;
    }

    /**
     * @return mixed
     */
    public function getGoldenThreshold()
    {
        return $this->golden_threshold;
    }

    /**
     * @param mixed $golden_threshold
     */
    public function setGoldenThreshold($golden_threshold): void
    {
        $this->golden_threshold = $golden_threshold;
    }

    /**
     * @return mixed
     */
    public function getSilverThreshold()
    {
        return $this->silver_threshold;
    }

    /**
     * @param mixed $silver_threshold
     */
    public function setSilverThreshold($silver_threshold): void
    {
        $this->silver_threshold = $silver_threshold;
    }

    /**
     * @return mixed
     */
    public function getBronzeThreshold()
    {
        return $this->bronze_threshold;
    }

    /**
     * @param mixed $bronze_threshold
     */
    public function setBronzeThreshold($bronze_threshold): void
    {
        $this->bronze_threshold = $bronze_threshold;
    }

    /**
     * @return mixed
     */
    public function getNameCa()
    {
        return $this->name_ca;
    }

    /**
     * @param mixed $name_ca
     */
    public function setNameCa($name_ca): void
    {
        $this->name_ca = $name_ca;
    }

}