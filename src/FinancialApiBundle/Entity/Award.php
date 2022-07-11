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
     * @ORM\Column(type="simple_array")
     * @Serializer\Groups({"public"})
     */
    private $thresholds;

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

    /**
     * @return mixed
     */
    public function getThresholds()
    {
        return $this->thresholds;
    }

    /**
     * @param mixed $thresholds
     */
    public function setThresholds($thresholds): void
    {
        $this->thresholds = $thresholds;
    }

}