<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Class Neighbourhood
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Neighbourhood extends AppObject implements Translatable, Localizable {

    use LocalizableTrait;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string")
     * @Groups({"public"})
     */
    private $name;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"public"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $townhall_code;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $bounds;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Group", mappedBy="neighbourhood")
     * @Groups({"self"})
     */
    private $accounts;

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
     * @VirtualProperty(name="translations")
     * @Groups({"public"})
     */
    public function getTranslations(){
        return $this->translations;
    }

}