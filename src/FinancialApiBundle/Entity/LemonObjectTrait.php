<?php


namespace App\FinancialApiBundle\Entity;


trait LemonObjectTrait {

    /**
     * @var string $lemon_reference
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $lemon_reference;

    /**
     * @var integer $lemon_status
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"user"})
     */
    private $lemon_status;

    /**
     * @return string
     */
    public function getLemonReference(): string
    {
        return $this->lemon_reference;
    }

    /**
     * @param string $lemon_reference
     */
    public function setLemonReference(string $lemon_reference): void
    {
        $this->lemon_reference = $lemon_reference;
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