<?php

namespace App\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class CreditCard{

    public function __construct(){
        $this->created = new \DateTime();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     * @Groups({"manager"})
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     * @Groups({"manager"})
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Expose
     * @Groups({"manager"})
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\User", inversedBy="bank_cards")
     * @Expose
     * @Groups({"manager"})
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"manager"})
     */
    private $alias;

    /**
     * @ORM\Column(type="string")
     * @Groups({"manager"})
     */
    private $external_id;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"manager"})
     */
    private $deleted = false;

    /**
     * Returns the user unique id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param mixed $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * @param mixed $external_id
     */
    public function setExternalId($external_id)
    {
        $this->external_id = $external_id;
    }

    /**
     * @return mixed
     */
    public function getDeleted(){
        return $this->deleted;
    }

    /**
     * @return boolean
     */
    public function isDeleted(){
        return $this->getDeleted();
    }

    /**
     * @param mixed $deleted
     */
    public function setDeleted($deleted){
        $this->deleted = $deleted;
    }
}