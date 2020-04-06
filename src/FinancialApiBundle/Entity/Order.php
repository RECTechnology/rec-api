<?php

namespace App\FinancialApiBundle\Entity;

use JMS\Serializer\Serializer;
use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Entity\AppObject;

/**
 * @ORM\Entity
 */
class Order extends AppObject
{
    static $STATUS_CREATED = 'created';
    static $STATUS_IN_PROGRESS = 'in-progress';
    static $STATUS_EXPIRED = 'expired';
    static $STATUS_DONE = 'done';
    static $STATUS_REFUNDED = 'refunded';

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
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     * @Serializer\Required()
     */
    public $url_ko;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    public $url_ok;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\POS", inversedBy="orders")
     * @Serializer\Groups({"user"})
     */
    public $pos;
}
