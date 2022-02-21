<?php
/**
 *  Author: Lluis Santos
 *  Date: 12 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Annotations as REC;
use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductKind
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class ProductKind extends AppObject implements Translatable, PreDeleteChecks {

    public const STATUS_CREATED = "created";
    public const STATUS_REVIEWED = "reviewed";

    use TranslatableTrait;

    /**
     * @REC\TranslatedProperty
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @Groups({"public"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @Groups({"manager"})
     */
    private $name_es;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @Groups({"manager"})
     */
    private $name_ca;

    /**
     * @REC\TranslatedProperty
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"public"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"manager"})
     */
    private $description_es;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"manager"})
     */
    private $description_ca;

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice(
     *     choices={"created", "reviewed"},
     *     message="Invalid parameter status, valid options: created, reviewed"
     * )
     * @Groups({"public"})
     */
    private $status;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Group",
     *     inversedBy="producing_products",
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinTable(name="accounts_products_producing")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $producing_by;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Group",
     *     inversedBy="consuming_products",
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinTable(name="accounts_products_consuming")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $consuming_by;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Activity",
     *     inversedBy="default_producing_products",
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinTable(name="activities_products_producing")
     * @Groups({"public"})
     * @Serializer\MaxDepth(2)
     */
    private $default_producing_by;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Activity",
     *     inversedBy="default_consuming_products",
     *     fetch="EXTRA_LAZY"
     * )
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
     * @throws PreconditionFailedException
     */
    public function addProducingBy(Group $producer, $recursive = true): void
    {
        if($this->producing_by->contains($producer)){
            throw new PreconditionFailedException("Account already related to this ProductKind");
        }
        $this->producing_by []= $producer;
        if($recursive) $producer->addProducingProduct($this, false);
    }

    /**
     * @param mixed $producer
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delProducingBy(Group $producer, $recursive = true): void
    {
        if(!$this->producing_by->contains($producer)){
            throw new PreconditionFailedException("Account not related to this ProductKind");
        }
        $this->producing_by->removeElement($producer);
        //if($recursive) $producer->delProducingProduct($this, false);
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
     * @throws PreconditionFailedException
     */
    public function addConsumingBy(Group $consumer, $recursive = true): void
    {
        if($this->consuming_by->contains($consumer)){
            throw new PreconditionFailedException("Account already consuming ProductKind");
        }
        $this->consuming_by []= $consumer;
        if($recursive) $consumer->addConsumingProduct($this, false);
    }

    /**
     * @param mixed $consumer
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delConsumingBy(Group $consumer, $recursive = true): void
    {
        if(!$this->consuming_by->contains($consumer)){
            throw new PreconditionFailedException("Account not related to this ProductKind");
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
     * @throws PreconditionFailedException
     */
    public function addDefaultProducingBy(Activity $activity, $recursive = true): void
    {
        if($this->default_producing_by->contains($activity)){
            throw new PreconditionFailedException("Activity already related to this ProductKind");
        }
        $this->default_producing_by []= $activity;
        if($recursive) $activity->addDefaultProducingProduct($this, false);
    }

    /**
     * @param mixed $activity
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delDefaultProducingBy(Activity $activity, $recursive = true): void
    {
        if(!$this->default_producing_by->contains($activity)){
            throw new PreconditionFailedException("Activity not related to this ProductKind");
        }
        $this->default_producing_by->removeElement($activity);
        //if($recursive) $activity->delDefaultProducingProduct($this, false);
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
     * @throws PreconditionFailedException
     */
    public function addDefaultConsumingBy(Activity $activity, $recursive = true): void
    {
        if($this->consuming_by->contains($activity)){
            throw new PreconditionFailedException("Activity already related to this ProductKind");
        }
        $this->default_consuming_by []= $activity;
        if($recursive) $activity->addDefaultConsumingProducts($this, false);
    }

    /**
     * @param mixed $activity
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delDefaultConsumingBy(Activity $activity, $recursive = true): void
    {
        if(!$this->default_consuming_by->contains($activity)){
            throw new PreconditionFailedException("Activity not related to this ProductKind");
        }
        $this->default_consuming_by->removeElement($activity);
        //if($recursive) $activity->delDefaultConsumingProduct($this, false);
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
    public function getNameEs()
    {
        return $this->name_es;
    }

    /**
     * @param mixed $name_es
     */
    public function setNameEs($name_es): void
    {
        $this->name_es = $name_es;
    }

    /**
     * @return mixed
     */
    public function getNameCa()
    {
        return $this->name_ca;
    }

    /**
     * @param mixed $name_ca
     */
    public function setNameCa($name_ca): void
    {
        $this->name_ca = $name_ca;
    }

    /**
     * @return mixed
     */
    public function getDescriptionEs()
    {
        return $this->description_es;
    }

    /**
     * @param mixed $description_es
     */
    public function setDescriptionEs($description_es): void
    {
        $this->description_es = $description_es;
    }

    /**
     * @return mixed
     */
    public function getDescriptionCa()
    {
        return $this->description_ca;
    }

    /**
     * @param mixed $description_ca
     */
    public function setDescriptionCa($description_ca): void
    {
        $this->description_ca = $description_ca;
    }
}