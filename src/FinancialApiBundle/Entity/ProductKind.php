<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductKind
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class ProductKind extends AppObject implements Translatable, Localizable, PreDeleteChecks {

    public const STATUS_CREATED = "created";
    public const STATUS_REVIEWED = "reviewed";

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
     * @ORM\Column(type="string")
     * @Assert\Choice({"created", "reviewed"})
     * @Groups({"public"})
     */
    private $status;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="producing_products")
     * @ORM\JoinTable(name="accounts_products_producing")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $producing_by;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="consuming_products")
     * @ORM\JoinTable(name="accounts_products_consuming")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $consuming_by;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Activity", inversedBy="default_producing_products")
     * @ORM\JoinTable(name="activities_products_producing")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $default_producing_by;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Activity", inversedBy="default_consuming_products")
     * @ORM\JoinTable(name="activities_products_consuming")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $default_consuming_by;

    /**
     * ProductKindOld constructor.
     */
    public function __construct() {
        $this->producing_by = new ArrayCollection();
        $this->consuming_by = new ArrayCollection();
        $this->default_consuming_by = new ArrayCollection();
        $this->default_producing_by = new ArrayCollection();
        $this->status = self::STATUS_CREATED;
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
    public function setName($name)
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
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getProducingBy()
    {
        return $this->producing_by;
    }

    /**
     * @param Group $producer
     * @param bool $recursive
     */
    public function addProducingBy(Group $producer, $recursive = true): void
    {
        $this->producing_by []= $producer;
        if($recursive) $producer->addProducingProduct($this, false);
    }

    /**
     * @param mixed $producer
     * @param bool $recursive
     */
    public function delProducingBy(Group $producer, $recursive = true): void
    {
        if(!$this->producing_by->contains($producer)){
            throw new \LogicException("Account not related to this ProductKind");
        }
        $this->producing_by->removeElement($producer);
        if($recursive) $producer->delProducingProduct($this, false);
    }

    /**
     * @return mixed
     */
    public function getConsumingBy()
    {
        return $this->consuming_by;
    }

    /**
     * @param Group $consumer
     * @param bool $recursive
     */
    public function addConsumingBy(Group $consumer, $recursive = true): void
    {
        $this->consuming_by []= $consumer;
        if($recursive) $consumer->addConsumingProduct($this, false);
    }

    /**
     * @param mixed $consumer
     * @param bool $recursive
     */
    public function delConsumingBy(Group $consumer, $recursive = true): void
    {
        if(!$this->consuming_by->contains($consumer)){
            throw new \LogicException("Account not related to this ProductKind");
        }
        $this->consuming_by->removeElement($consumer);
        if($recursive) $consumer->delConsumingProduct($this, false);
    }

    /**
     * @return mixed
     */
    public function getDefaultProducingBy()
    {
        return $this->default_producing_by;
    }

    /**
     * @param mixed $activity
     * @param bool $recursive
     */
    public function addDefaultProducingBy(Activity $activity, $recursive = true): void
    {
        $this->default_producing_by []= $activity;
        if($recursive) $activity->addDefaultProducingProduct($this, false);
    }

    /**
     * @param mixed $activity
     * @param bool $recursive
     */
    public function delDefaultProducingBy(Activity $activity, $recursive = true): void
    {
        if(!$this->default_producing_by->contains($activity)){
            throw new \LogicException("Activity not related to this ProductKind");
        }
        $this->default_producing_by->removeElement($activity);
        if($recursive) $activity->delDefaultProducingProduct($this, false);
    }

    /**
     * @return mixed
     */
    public function getDefaultConsumingBy()
    {
        return $this->default_consuming_by;
    }

    /**
     * @param mixed $activity
     * @param bool $recursive
     */
    public function addDefaultConsumingBy(Activity $activity, $recursive = true): void
    {
        $this->default_consuming_by []= $activity;
        if($recursive) $activity->addDefaultConsumingProducts($this, false);
    }

    /**
     * @param mixed $activity
     * @param bool $recursive
     */
    public function delDefaultConsumingBy(Activity $activity, $recursive = true): void
    {
        if(!$this->default_consuming_by->contains($activity)){
            throw new \LogicException("Activity not related to this ProductKind");
        }
        $this->default_consuming_by->removeElement($activity);
        if($recursive) $activity->delDefaultConsumingProduct($this, false);
    }

    /**
     * @throws PreconditionFailedException
     */
    function isDeleteAllowed()
    {
        if(!$this->producing_by->isEmpty())
            throw new PreconditionFailedException("Deletion forbidden: product produced by (1+) accounts");
        if(!$this->consuming_by->isEmpty())
            throw new PreconditionFailedException("Deletion forbidden: product consumed by (1+) accounts");
        if(!$this->default_producing_by->isEmpty())
            throw new PreconditionFailedException("Deletion forbidden: product produced by (1+) activities");
        if(!$this->default_consuming_by->isEmpty())
            throw new PreconditionFailedException("Deletion forbidden: product consumed by (1+) activities");
    }

    /**
     * @param $status
     */
    public function setStatus($status): void
    {
        return $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}