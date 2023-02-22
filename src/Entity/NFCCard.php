<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace App\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Financial\Currency;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class NFCCard{

    public function __construct(){
        $this->created = new \DateTime();
        $this->last_pin_requested = new \DateTime();
        $this->last_disable_requested = new \DateTime();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @Expose
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $alias;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Expose
     */
    private $id_card;

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
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param mixed $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return mixed
     */
    public function getIdCard()
    {
        return $this->id_card;
    }

    /**
     * @param mixed $id_card
     */
    public function setIdCard($id_card)
    {
        $this->id_card = $id_card;
    }

}