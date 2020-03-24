<?php


namespace App\FinancialApiBundle\Entity;


trait ExternalObjectTrait {


    /**
     * @var string $external_reference
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $external_reference;

    /**
     * @var array $external_info
     * @ORM\Column(type="json", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $external_info;

    /**
     * @var boolean $auto_fetched
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"user"})
     */
    private $auto_fetched;

    /**
     * @return array
     */
    public function getExternalInfo(): ?array
    {
        return $this->external_info;
    }

    /**
     * @param $external_info
     */
    public function setExternalInfo($external_info): void
    {
        $this->external_info = $external_info;
    }

    /**
     * @return string
     */
    public function getExternalReference(): ?string
    {
        return $this->external_reference;
    }

    /**
     * @param string $external_reference
     */
    public function setExternalReference(string $external_reference): void
    {
        $this->external_reference = $external_reference;
    }

    /**
     * @return bool
     * Returns if the object is auto-fetched from the provider
     */
    public function isAutoFetched(): bool {
        if($this->auto_fetched == null) return false;
        return $this->auto_fetched;
    }

    /**
     * @param $auto_fetched
     */
    public function setAutoFetched($auto_fetched) {
        $this->auto_fetched = $auto_fetched;
    }


}