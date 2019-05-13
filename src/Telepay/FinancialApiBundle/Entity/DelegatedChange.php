<?php

namespace Telepay\FinancialApiBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;


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


}