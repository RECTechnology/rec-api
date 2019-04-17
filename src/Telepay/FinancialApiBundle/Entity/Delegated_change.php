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
class Delegated_change {

    public function __construct()
    {
        //$this->scheduled_time = new DateTime();
        $this->groups = new ArrayCollection();
    }


    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
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














}