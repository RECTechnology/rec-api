<?php

namespace App\FinancialApiBundle\Entity;

use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Entity\AppObject;
use App\FinancialApiBundle\Annotations\StatusProperty;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class PaymentOrder extends AppObject implements Stateful
{
    const STATUS_IN_PROGRESS = 'in-progress';
    const STATUS_EXPIRED = 'expired';
    const STATUS_DONE = 'done';
    const STATUS_REFUNDED = 'refunded';

    use StatefulTrait;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "created"={"to"={"in-progress", "expired"}},
     *     "in-progress"={"to"={"done", "expired"}},
     *     "done"={"to"={"refunded"}},
     *     "expired"={"final"=true},
     *     "refunded"={"final"=true},
     * }, initial_statuses={"created"})
     * @Serializer\Groups({"user"})
     */
    protected $status;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $amount;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     * @Assert\Url()
     */
    private $ko_url;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     * @Assert\Url()
     */
    private $ok_url;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Pos", inversedBy="orders")
     * @Serializer\Groups({"user"})
     */
    private $pos;

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getKoUrl()
    {
        return $this->ko_url;
    }

    /**
     * @param mixed $ko_url
     */
    public function setKoUrl($ko_url)
    {
        $this->ko_url = $ko_url;
    }

    /**
     * @return mixed
     */
    public function getOkUrl()
    {
        return $this->ok_url;
    }

    /**
     * @param mixed $ok_url
     */
    public function setOkUrl($ok_url)
    {
        $this->ok_url = $ok_url;
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
    public function setPos($pos)
    {
        $this->pos = $pos;
    }
}
