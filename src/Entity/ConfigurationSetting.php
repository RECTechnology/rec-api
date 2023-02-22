<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class ConfigurationSetting
 * @package App\Entity
 * @ORM\Entity
 */
class ConfigurationSetting extends AppObject
{
    public const SHOP_BADGES_SCOPE = 'badges';
    public const QUALIFICATIONS_SCOPE = 'qualifications';
    public const APP_SCOPE = 'app';
    public const NFT_SCOPE = 'nft_wallet';
    public const ADMIN_PANEL_SCOPE = 'admin_panel';

    public const SETTING_QUALIFICATIONS_SYSTEM_STATUS = 'qualifications_system_status';
    public const SETTING_C2B_CHALLENGES_STATUS = 'c2b_challenges_status';


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
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $platform;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Package")
     * @Serializer\Groups({"admin"})
     */
    private $package;

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
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param mixed $platform
     */
    public function setPlatform($platform): void
    {
        $this->platform = $platform;
    }

    /**
     * @return mixed
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param mixed $package
     */
    public function setPackage($package): void
    {
        $this->package = $package;
    }
}