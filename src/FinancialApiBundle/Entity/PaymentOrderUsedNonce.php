<?php

namespace App\FinancialApiBundle\Entity;

use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PaymentOrderUsedNonce extends AppObject
{

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @Serializer\Groups({"admin"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Pos")
     * @Serializer\Groups({"admin"})
     */
    private $pos;


    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $nonce;

    /**
     * @return mixed
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param mixed $nonce
     */
    public function setNonce($nonce): void
    {
        $this->nonce = $nonce;
    }

    /**
     * @return mixed
     */
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * @param mixed $pos
     */
    public function setPos($pos): void
    {
        $this->pos = $pos;
    }
}
