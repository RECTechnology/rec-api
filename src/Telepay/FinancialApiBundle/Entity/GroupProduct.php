<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user_user_group")
 * @ExclusionPolicy("all")
 */
class GroupProduct{

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $group;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Product")
     */
    private $product;


    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }
}