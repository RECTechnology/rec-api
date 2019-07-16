<?php

namespace Telepay\FinancialApiBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;


/**
 * @ORM\MappedSuperclass
 */
class AppObject {

    public function __construct(){
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"public"})
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"user"})
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"user"})
     */
    private $updated;

    /**
     * Returns the object unique id.
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
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