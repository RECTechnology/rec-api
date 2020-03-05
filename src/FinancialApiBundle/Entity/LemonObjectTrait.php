<?php


namespace App\FinancialApiBundle\Entity;


trait LemonObjectTrait {

    use ExternalObjectTrait;

    /**
     * @var integer $lemon_status
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $lemon_status;

    /**
     * @return string
     */
    public function getLemonReference(): string
    {
        return $this->getExternalReference();
    }

    /**
     * @param string $lemon_reference
     */
    public function setLemonReference(string $lemon_reference): void
    {
        $this->setExternalReference($lemon_reference);
    }

    /**
     * @param $lemon_status
     */
    public function setLemonStatus($lemon_status)
    {
        $this->lemon_status = $lemon_status;
    }

    /**
     * @return integer
     */
    public function getLemonStatus(): int
    {
        return $this->lemon_status;
    }
}