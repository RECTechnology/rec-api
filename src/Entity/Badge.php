<?php

namespace App\Entity;

use App\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Badge
 * @package App\Entity
 * @ORM\Entity
 */
class Badge extends AppObject
{
    /**
     * Badge constructor.
     */
    public function __construct() {
        $this->accounts = new ArrayCollection();
        $this->challenges = new ArrayCollection();
    }


    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"public"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"public"})
     */
    private $name_es;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"public"})
     */
    private $name_ca;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $description_es;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $description_ca;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"admin"})
     */
    private $enabled = false;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\Entity\Group",
     *     inversedBy="badges",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(2)
     */
    private $accounts;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $image_url;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $group_name;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $group_name_es;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $group_name_ca;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\Entity\Challenge",
     *     inversedBy="badges",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    private $challenges;

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

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
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
     * @throws PreconditionFailedException
     */
    public function addAccount(Group $account, $recursive = true): void
    {
        if($this->accounts->contains($account)){
            throw new PreconditionFailedException("Account already related to this Badge");
        }
        $this->accounts []= $account;
        if($recursive) $account->addBadge($this, false);
    }

    /**
     * @param Group $account
     * @param bool $recursive
     */
    public function delAccount(Group $account, $recursive = true): void
    {
        if(!$this->accounts->contains($account)){
            throw new PreconditionFailedException("Account not related to this Badge");
        }
        $this->accounts->removeElement($account);
        if($recursive) $account->delBadge($this, false);
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }

    /**
     * @param mixed $image_url
     */
    public function setImageUrl($image_url): void
    {
        $this->image_url = $image_url;
    }

    /**
     * @return mixed
     */
    public function getGroupName()
    {
        return $this->group_name;
    }

    /**
     * @param mixed $group_name
     */
    public function setGroupName($group_name): void
    {
        $this->group_name = $group_name;
    }

    /**
     * @return mixed
     */
    public function getGroupNameEs()
    {
        return $this->group_name_es;
    }

    /**
     * @param mixed $group_name_es
     */
    public function setGroupNameEs($group_name_es): void
    {
        $this->group_name_es = $group_name_es;
    }

    /**
     * @return mixed
     */
    public function getGroupNameCa()
    {
        return $this->group_name_ca;
    }

    /**
     * @param mixed $group_name_ca
     */
    public function setGroupNameCa($group_name_ca): void
    {
        $this->group_name_ca = $group_name_ca;
    }

    /**
     * @return ArrayCollection
     */
    public function getChallenges()
    {
        return $this->challenges;
    }

    /**
     * @param Challenge $challenge
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function addChallenge(Challenge $challenge, $recursive = true): void
    {
        if($this->challenges->contains($challenge)){
            throw new PreconditionFailedException("Badge already related to this Challenge");
        }
        $this->challenges []= $challenge;
        if($recursive) $challenge->addBadge($this, false);
    }

    /**
     * @param Challenge $challenge
     * @param bool $recursive
     */
    public function delChallenge(Challenge $challenge, $recursive = true): void
    {
        if(!$this->challenges->contains($challenge)){
            throw new PreconditionFailedException("Badge not related to this Challenge");
        }
        $this->challenges->removeElement($challenge);
        if($recursive) $challenge->delBadge($this, false);
    }

}