<?php

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;


/**
 * @ORM\Entity
 * @ORM\Table(name="Delegated_change")
 * @ExclusionPolicy("all")
 */
class DelegatedChange {

    public function __construct()
    {
        //$this->scheduled_time = new DateTime();
        $this->data = new ArrayCollection();
    }


    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\DelegatedChangeData", mappedBy="delegated_change", cascade={"remove"})
     */
    protected $data;

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }



    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */

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


    private $scheduled_time;

    /**
     * @return mixed
     */
    public function getScheduledTime()
    {
        return $this->scheduled_time;
    }


    /**
     * @param mixed $scheduled_time
     */
    public function setScheduledTime($scheduled_time)
    {
        $this->scheduled_time = $scheduled_time;
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














}