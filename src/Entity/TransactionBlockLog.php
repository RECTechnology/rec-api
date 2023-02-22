<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use LogicException;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraint as RECAssert;


/**
 * @ORM\Entity
 * @ORM\Table(name="transaction_block_log")
 */
class TransactionBlockLog extends AppObject {

    const TYPE_DEBUG = "debug";
    const TYPE_WARN = "warn";
    const TYPE_ERROR = "error";

    /**
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\DelegatedChange", inversedBy="logs")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    private $block_txs;


    /**
     * @ORM\Column(type="string")
     * @Assert\Choice({"DEBUG", "WARN", "ERROR"})
     * @Serializer\Groups({"admin"})
     */
    private $type;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $log;

    /**
     * @return mixed
     */
    public function getBlockTxs()
    {
        return $this->block_txs;
    }

    /**
     * @param mixed $block_txs
     */
    public function setBlockTxs($block_txs): void
    {
        $this->block_txs = $block_txs;
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
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param mixed $log
     */
    public function setLog($log): void
    {
        $this->log = $log;
    }


}