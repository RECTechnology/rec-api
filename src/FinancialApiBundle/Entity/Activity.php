<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\FinancialApiBundle\Annotations as REC;

/**
 * Class Activity
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Activity extends AppObject implements Translatable, PreDeleteChecks {

    public const STATUS_CREATED = "created";
    public const STATUS_REVIEWED = "reviewed";

    use TranslatableTrait;

    /**
     * @REC\TranslatedProperty
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"manager"})
     */
    private $name_es;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"manager"})
     */
    private $name_ca;

    /**
     * @REC\TranslatedProperty
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"manager"})
     */
    private $description_es;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"manager"})
     */
    private $description_ca;

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice({"created", "reviewed"})
     * @Serializer\Groups({"public"})
     */
    private $status;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="activities")
     * @Serializer\Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $accounts;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\ProductKind", mappedBy="default_producing_by")
     * @Serializer\Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $default_producing_products;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\ProductKind", mappedBy="default_consuming_by")
     * @Serializer\Groups({"public"})
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
        $this->status = self::STATUS_CREATED;
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

    /**
     * @throws PreconditionFailedException
     */
    function isDeleteAllowed()
    {
        if(!$this->accounts->isEmpty())
            throw new PreconditionFailedException("Delete forbidden: activity is assigned to (1+) accounts");
        if(!$this->default_consuming_products->isEmpty())
            throw new PreconditionFailedException("Delete forbidden: activity has (1+) consuming products");
        if(!$this->default_consuming_products->isEmpty())
            throw new PreconditionFailedException("Delete forbidden: activity has (1+) consuming products");
    }

    /**
     * @param $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
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
     */
    public function setName($name): void
    {
        $this->name = $name;
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
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

}