<?php

namespace App\FinancialApiBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class ConfigurationSetting
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class ConfigurationSetting extends AppObject
{
    public const SHOP_BADGES_SCOPE = 'shop_badges';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $scope;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $value;

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param mixed $scope
     */
    public function setScope($scope): void
    {
        $this->scope = $scope;
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
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}