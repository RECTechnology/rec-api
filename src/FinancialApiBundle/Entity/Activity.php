<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Activity
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Activity extends AppObject {

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
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Activity
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
     * @return Activity
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

}