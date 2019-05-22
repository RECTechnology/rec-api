<?php

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
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 * @ExclusionPolicy("all")
 *
 * @ORM\AttributeOverrides({
 *     @ORM\AttributeOverride(name="emailCanonical",
 *         column=@ORM\Column(
 *             name="emailCanonical",
 *             type="string",
 *             length=255,
 *             unique=false
 *         )
 *     )
 * })
 */
class User extends BaseUser implements EntityWithUploadableFields
{
    public function __construct()
    {
        parent::__construct();
        $this->groups = new ArrayCollection();
        $this->devices = new ArrayCollection();

        if($this->access_key == null){
            $generator = new SecureRandom();
            $this->access_key=sha1($generator->nextBytes(32));
            $this->access_secret=base64_encode($generator->nextBytes(32));
        }
        $this->created = new \DateTime();
        $this->bank_cards = new ArrayCollection();
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
     * @ORM\Column(type="string")
     */
    private $pin;

    /**
     * @ORM\Column(type="string")
     */
    private $security_question;

    /**
     * @ORM\Column(type="string")
     */
    private $security_answer;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Expose
     */
    private $dni;

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
     * @ORM\Column(type="string", unique=true)
     * @Expose
     */
    private $phone;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $public_phone;

    /**
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $prefix;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Expose
     */
    private $profile_image = '';

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
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\TierValidations", mappedBy="user", cascade={"remove"})
     */
    private $tier_validations;

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\KYC", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $kyc_validations;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\CreditCard", mappedBy="user", cascade={"remove"})
     */
    private $bank_cards;

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
     * @return bool
     * @VirtualProperty()
     * @SerializedName("has_saved_cards")
     * @Type("boolean")
     */
    public function hasSavedCards(){

        /** @var CreditCard $card */
        foreach ($this->getBankCards() as $card) {
            if(!$card->isDeleted()) return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getDNI()
    {
        return $this->dni;
    }

    /**
     * @param mixed $dni
     */
    public function setDNI($dni)
    {
        $this->dni = $dni;
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
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getPublicPhone()
    {
        return $this->public_phone;
    }

    /**
     * @param mixed $public_phone
     */
    public function setPublicPhone($public_phone)
    {
        $this->public_phone = $public_phone;
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
    public function getSecurityQuestion()
    {
        return $this->security_question;
    }
    /**
     * @param mixed $security_question
     */
    public function setSecurityQuestion($security_question)
    {
        $this->security_question = $security_question;
    }

    /**
     * @return mixed
     */
    public function getSecurityAnswer()
    {
        return $this->security_answer;
    }

    /**
     * @param mixed $security_answer
     */
    public function setSecurityAnswer($security_answer)
    {
        $this->security_answer = $security_answer;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param mixed $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
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
        return in_array(strtoupper('ROLE_KYC'), $this->roles, true);
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
//        $this->groups = $this->getGroups();
        return $this;
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

    /**
     * @return mixed
     */
    public function getProfileImage()
    {
        return $this->profile_image;
    }

    /**
     * @param mixed $profile_image
     */
    public function setProfileImage($profile_image)
    {
        $this->profile_image = $profile_image;
    }

    function getUploadableFields()
    {
        return ['profile_image' => UploadManager::$FILTER_IMAGES];
    }

    /**
     * @return mixed
     */
    public function getBankCards()
    {
        return $this->bank_cards;
    }
}