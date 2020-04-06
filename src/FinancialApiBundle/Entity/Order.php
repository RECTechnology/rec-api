<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Entity\AppObject;

/**
 * @ORM\Entity
 */
class Order extends AppObject
{
    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "created"={"to"={"in-progress", "in-progress", "expired"}},
     *     "in-progress"={"to"={"done", "refunded", "expired"}},
     *     "expired"={"final"=true},
     *     "done"={"final"=true},
     *     "refunded"={"final"=true},
     * }, initial_statuses="created")
     * @Serializer\Groups({"user"})
     */
    protected $status;

    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"user"})
     */
    public $amount;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\POS", inversedBy="orders")
     * @Serializer\Groups({"user"})
     */
    public $pos;
}
