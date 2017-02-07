<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Util\SecureRandom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 * @ExclusionPolicy("all")
 */
class User extends BaseUser
{
    public function __construct()
    {
        parent::__construct();
        $this->groups = new ArrayCollection();
        $this->btc_addresses = new ArrayCollection();
        $this->devices = new ArrayCollection();

        if($this->access_key == null){
            $generator = new SecureRandom();
            $this->access_key=sha1($generator->nextBytes(32));
            $this->access_secret=base64_encode($generator->nextBytes(32));
        }
        $this->created = new \DateTime();
    }
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\UserGroup", mappedBy="user", cascade={"remove"})
     */
    protected $groups;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $active_group = null;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\AccessToken", mappedBy="user", cascade={"remove"})
     *
     */
    private $access_token;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\RefreshToken", mappedBy="user", cascade={"remove"})
     */
    private $refresh_token;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\AuthCode", mappedBy="user", cascade={"remove"})
     */
    private $auth_code;

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\BTCWallet", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $btc_wallet;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\BTCAddresses", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $btc_addresses;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Device", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $devices;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $access_key;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $access_secret;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     * @Expose
     */
    private $base64_image;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $gcm_group_key;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $twoFactorAuthentication = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $twoFactorCode;

    /**
     * @Expose
     */
    private $group_data = array();

    /**
     * @Expose
     */
    private $kyc_data = array();

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\TierValidations", mappedBy="user", cascade={"remove"})
     */
    private $tier_validations;

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\KYC", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $kyc_validations;

    /**
     * Random string sent to the user email address in order to recover the password
     *
     * @ORM\Column(type="string", nullable=true)
     * @Exclude
     */
    private $recover_password_token;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    private $created;

    /**
     * @Expose
     */
    protected $lastLogin;

    public function getAccessKey(){
        return $this->access_key;
    }

    public function getAccessSecret(){
        return $this->access_secret;
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
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * @param mixed $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * @param mixed $refresh_token
     */
    public function setRefreshToken($refresh_token)
    {
        $this->refresh_token = $refresh_token;
    }

    /**
     * @return mixed
     */
    public function getAuthCode()
    {
        return $this->auth_code;
    }

    /**
     * @param mixed $auth_code
     */
    public function setAuthCode($auth_code)
    {
        $this->auth_code = $auth_code;
    }

    /**
     * @return mixed
     */
    public function getBase64Image()
    {
        return $this->base64_image;
    }

    /**
     * @param mixed $base64_image
     */
    public function setBase64Image($base64_image)
    {
        $this->base64_image = $base64_image;
    }

    /**
     * @return mixed
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * @param mixed $access_key
     */
    public function setAccessKey($access_key)
    {
        $this->access_key = $access_key;
    }

    /**
     * @param mixed $access_secret
     */
    public function setAccessSecret($access_secret)
    {
        $this->access_secret = $access_secret;
    }

    /**
     * @return mixed
     */
    public function getBtcWallet()
    {
        return $this->btc_wallet;
    }

    /**
     * @param mixed $btc_wallet
     */
    public function setBtcWallet($btc_wallet)
    {
        $this->btc_wallet = $btc_wallet;
    }

    /**
     * @return mixed
     */
    public function getBtcAddresses()
    {
        return $this->btc_addresses;
    }

    /**
     * @param mixed $btc_addresses
     */
    public function setBtcAddresses($btc_addresses)
    {
        $this->btc_addresses = $btc_addresses;
    }

    /**
     * @return mixed
     */
    public function getGcmGroupKey()
    {
        return $this->gcm_group_key;
    }

    /**
     * @param mixed $gcm_group_key
     */
    public function setGcmGroupKey($gcm_group_key)
    {
        $this->gcm_group_key = $gcm_group_key;
    }

    /**
     * @param array $group_data
     */
    public function setGroupData($group_data)
    {
        $this->group_data = $group_data;
    }

    /**
     * @param array $kyc_data
     */
    public function setKycData($kyc_data)
    {
        $this->kyc_data = $kyc_data;
    }

    /**
     * @return mixed
     */
    public function getTierValidations()
    {
        return $this->tier_validations;
    }

    /**
     * @param mixed $tier_validations
     */
    public function setTierValidations($tier_validations)
    {
        $this->tier_validations = $tier_validations;
    }

    /**
     * @return mixed
     */
    public function getTwoFactorAuthentication()
    {
        return $this->twoFactorAuthentication;
    }

    /**
     * @param mixed $twoFactorAuthentication
     */
    public function setTwoFactorAuthentication($twoFactorAuthentication)
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;
    }

    /**
     * @return mixed
     */
    public function getTwoFactorCode()
    {
        return $this->twoFactorCode;
    }

    /**
     * @param mixed $twoFactorCode
     */
    public function setTwoFactorCode($twoFactorCode)
    {
        $this->twoFactorCode = $twoFactorCode;
    }

    /**
     * @return mixed
     */
    public function getRecoverPasswordToken()
    {
        return $this->recover_password_token;
    }

    /**
     * @param mixed $recover_password_token
     */
    public function setRecoverPasswordToken($recover_password_token)
    {
        $this->recover_password_token = $recover_password_token;
    }

    /**
     * Returns the user roles
     *
     * @return array The roles
     */
    public function getRoles(){
        foreach($this->groups as $Usergroup){
            if($this->getActiveGroup()->getId() == $Usergroup->getGroup()->getId()){
                $roles = $Usergroup->getRoles();
                $roles = array_merge($roles, $Usergroup->getGroup()->getRoles());
            }
        }
        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;
        return array_unique($roles);
    }

    /**
     * Returns a boolean depending on KYC type user
     * @return bool
     */
    public function isKYC(){
        return $this->hasRole('ROLE_KYC');
    }

    /**
     * Returns the user roles
     * @return array The roles
     */
    public function getRolesCompany($company_id)
    {
        foreach($this->groups as $Usergroup){
            if($company_id == $Usergroup->getGroup()->getId()){
                $roles = $Usergroup->getRoles();
                $roles = array_merge($roles, $Usergroup->getGroup()->getRoles());
            }
        }
        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;
        return array_unique($roles);
    }

    /**
     * @return mixed
     */
    public function getActiveGroup()
    {
        if($this->active_group == null || $this->active_group->getId() == 0){
            return  $this->getGroups()[0];
        }
        return $this->active_group;
    }

    /**
     * @param mixed $active_group
     */
    public function setActiveGroup($active_group)
    {
        $this->active_group = $active_group;
    }

    /**
     * Gets the groups granted to the user.
     *
     * @return Collection
     */
    public function getGroups()
    {
        $groups = new ArrayCollection();

        foreach($this->groups as $Usergroup){
            $groups->add($Usergroup->getGroup());
        }
        return $groups;
    }

    public function getGroupNames()
    {
        $names = array();
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasGroup($name)
    {
        return in_array($name, $this->getGroupNames());
    }

    public function getAdminView(){
        unset($this->btc_addresses);
        unset($this->access_secret);
        unset($this->access_key);
        unset($this->devices);
        unset ( $this->gcm_group_key);
        unset($this->twoFactorAuthentication);
        unset ($this->cash_in_tokens);
        unset($this->group_data);
        unset($this->kyc_data);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getKycValidations()
    {
        return $this->kyc_validations;
    }

    /**
     * @param mixed $kyc_validations
     */
    public function setKycValidations($kyc_validations)
    {
        $this->kyc_validations = $kyc_validations;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }
}