<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class ProductKind
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class ProductKind extends AppObject implements Translatable, Localizable {

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
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="producing_products")
     * @ORM\JoinTable(name="accounts_products_producing")
     * @Groups({"public"})
     */
    private $producing_by;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="consuming_products")
     * @ORM\JoinTable(name="accounts_products_consuming")
     * @Groups({"public"})
     */
    private $consuming_by;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Activity", inversedBy="default_producing_products")
     * @ORM\JoinTable(name="activities_products_producing")
     * @Groups({"public"})
     */
    private $default_producing_by;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Activity", inversedBy="default_consuming_products")
     * @ORM\JoinTable(name="activities_products_consuming")
     * @Groups({"public"})
     */
    private $default_consuming_by;

    /**
     * ProductKind constructor.
     */
    public function __construct() {
        $this->producing_by = new ArrayCollection();
        $this->consuming_by = new ArrayCollection();
        $this->default_consuming_by = new ArrayCollection();
        $this->default_producing_by = new ArrayCollection();
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
     * @return ProductKind
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
     * @return ProductKind
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

}