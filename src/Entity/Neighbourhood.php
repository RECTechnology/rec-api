<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\Entity;

use App\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Neighbourhood
 * @package App\Entity
 * @ORM\Entity
 */
class Neighbourhood extends AppObject implements PreDeleteChecks {

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $townhall_code;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $bounds;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Group", mappedBy="neighbourhood")
     * @Serializer\Groups({"manager"})
     */
    private $accounts;

    /**
     * Neighbourhood constructor.
     */
    public function __construct() {
        $this->accounts = new ArrayCollection();
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Neighbourhood
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return Neighbourhood
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * @param mixed $bounds
     * @return Neighbourhood
     */
    public function setBounds($bounds)
    {
        $this->bounds = $bounds;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTownhallCode()
    {
        return $this->townhall_code;
    }

    /**
     * @param mixed $townhall_code
     * @return Neighbourhood
     */
    public function setTownhallCode($townhall_code)
    {
        $this->townhall_code = $townhall_code;
        return $this;
    }


    /**
     * @inheritDoc
     */
    function isDeleteAllowed()
    {
        if(!$this->accounts->isEmpty())
            throw new PreconditionFailedException("Delete forbidden: neighbourhood is assigned to (1+) accounts");
    }
}