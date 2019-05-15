<?php

namespace Telepay\FinancialApiBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use LogicException;


/**
 * @ORM\Entity
 * @ORM\Table(name="delegated_change")
 * @ExclusionPolicy("all")
 */
class DelegatedChange {

    public function __construct()
    {
        $this->created = $this->updated = new DateTime();
        $this->data = new ArrayCollection();
        $this->status = "draft";
        $this->statistics = [
            "scheduled" => [
                "rec_to_be_issued" => 0.
            ],
            "result" => [
                "success_tx" => 0,
                "failed_tx" => 0,
                "issued_rec" => 0.,
            ]
        ];
    }


    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    protected $status;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\DelegatedChangeData", mappedBy="delegated_change", cascade={"remove"})
     */
    protected $data;

    /**
     * @return ArrayCollection
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    protected $created;


    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    protected $updated;


    /**
     * @ORM\Column(type="json_array")
     * @Expose
     */
    private $statistics;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Expose
     */
    private $scheduled_at;

    /**
     * @return mixed
     */
    public function getScheduledAt()
    {
        return $this->scheduled_at;
    }


    /**
     * @param mixed $scheduled_at
     * @throws \Exception
     */
    public function setScheduledAt($scheduled_at)
    {
        $this->scheduled_at = new DateTime($scheduled_at);
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param mixed $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getStatistics()
    {
        return $this->statistics;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function setResult($key, $value)
    {
        if(!array_key_exists($key, $this->statistics['result']))
            throw new LogicException("Key '$key' not found in result");
        $this->statistics['result'][$key] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setRecToBeIssued($value)
    {
        $this->statistics['scheduled']['rec_to_be_issued'] = $value;
    }


}