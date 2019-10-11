<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Activity
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Activity extends AppObject implements Translatable, Localizable {

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
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="activities")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $accounts;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\ProductKind", mappedBy="default_producing_by")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $default_producing_products;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\ProductKind", mappedBy="default_consuming_by")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $default_consuming_products;

    /**
     * Activity constructor.
     */
    public function __construct() {
        $this->accounts = new ArrayCollection();
        $this->default_consuming_products = new ArrayCollection();
        $this->default_producing_products = new ArrayCollection();
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

    /**
     * @return mixed
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * @param Group $account
     * @param bool $recursive
     */
    public function addAccount(Group $account, $recursive = true): void
    {
        $this->accounts []= $account;
        if($recursive) $account->addActivity($this, false);
    }

    /**
     * @param Group $account
     * @param bool $recursive
     */
    public function delAccount(Group $account, $recursive = true): void
    {
        if(!$this->accounts->contains($account)){
            throw new \LogicException("Account not related to this Activity");
        }
        $this->accounts->removeElement($account);
        if($recursive) $account->delActivity($this, false);
    }

    /**
     * @return mixed
     */
    public function getDefaultProducingProducts()
    {
        return $this->default_producing_products;
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     */
    public function addDefaultProducingProduct(ProductKind $product, $recursive = true): void
    {
        $this->default_producing_products []= $product;
        if($recursive) $product->addDefaultProducingBy($this, false);
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     */
    public function delDefaultProducingProduct(ProductKind $product, $recursive = true): void
    {
        if(!$this->default_producing_products->contains($product)){
            throw new \LogicException("ProductKind not related to this Activity");
        }
        $this->default_producing_products->removeElement($product);
        if($recursive) $product->delDefaultProducingBy($this, false);
    }

    /**
     * @return mixed
     */
    public function getDefaultConsumingProducts()
    {
        return $this->default_consuming_products;
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     */
    public function addDefaultConsumingProducts(ProductKind $product, $recursive = true): void
    {
        $this->default_consuming_products []= $product;
        if($recursive) $product->addDefaultConsumingBy($this, false);
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     */
    public function delDefaultConsumingProduct(ProductKind $product, $recursive = true): void
    {
        if(!$this->default_consuming_products->contains($product)){
            throw new \LogicException("ProductKind not related to this Activity");
        }
        $this->default_consuming_products->removeElement($product);
        if($recursive) $product->delDefaultConsumingBy($this, false);
    }
}