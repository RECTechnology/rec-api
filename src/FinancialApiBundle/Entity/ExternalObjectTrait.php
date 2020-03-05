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
     * @return array
     */
    public function getExternalInfo(): array
    {
        return $this->external_info;
    }

    /**
     * @param array $external_info
     */
    public function setExternalInfo(array $external_info): void
    {
        $this->external_info = $external_info;
    }

    /**
     * @return string
     */
    public function getExternalReference(): string
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


}