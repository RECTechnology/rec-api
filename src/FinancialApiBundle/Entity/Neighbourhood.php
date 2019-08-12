<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Neighbourhood
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Neighbourhood extends AppObject {

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     * @Groups({"public"})
     */
    private $description;

    /**
     * @ORM\Column(type="json_array")
     */
    private $bounds;

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

}