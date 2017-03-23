<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace Telepay\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Financial\Currency;

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
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
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
    private $enabled;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Expose
     */
    private $id_card;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $pin;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $confirmation_token;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $refresh_pin_token;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $new_pin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Expose
     */
    private $last_pin_requested;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Expose
     */
    private $last_disable_requested;

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

    /**
     * @return mixed
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * @param mixed $pin
     */
    public function setPin($pin)
    {
        $this->pin = $pin;
    }

    /**
     * @return mixed
     */
    public function getConfirmationToken()
    {
        return $this->confirmation_token;
    }

    /**
     * @param mixed $confirmation_token
     */
    public function setConfirmationToken($confirmation_token)
    {
        $this->confirmation_token = $confirmation_token;
    }

    /**
     * @return mixed
     */
    public function getRefreshPinToken()
    {
        return $this->refresh_pin_token;
    }

    /**
     * @param mixed $refresh_pin_token
     */
    public function setRefreshPinToken($refresh_pin_token)
    {
        $this->refresh_pin_token = $refresh_pin_token;
    }

    /**
     * @return mixed
     */
    public function getNewPin()
    {
        return $this->new_pin;
    }

    /**
     * @param mixed $new_pin
     */
    public function setNewPin($new_pin)
    {
        $this->new_pin = $new_pin;
    }

    /**
     * @return mixed
     */
    public function getLastPinRequested()
    {
        return $this->last_pin_requested;
    }

    /**
     * @param mixed $last_pin_requested
     */
    public function setLastPinRequested($last_pin_requested)
    {
        $this->last_pin_requested = $last_pin_requested;
    }

    /**
     * @return mixed
     */
    public function getLastDisableRequested()
    {
        return $this->last_disable_requested;
    }

    /**
     * @param mixed $last_disable_requested
     */
    public function setLastDisableRequested($last_disable_requested)
    {
        $this->last_disable_requested = $last_disable_requested;
    }

}