<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Util\SecureRandom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;

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
//        $this->limit_counts = new ArrayCollection();
//        $this->wallets = new ArrayCollection();
        $this->btc_addresses = new ArrayCollection();
        $this->devices = new ArrayCollection();
//        $this->clients = new ArrayCollection();

        if($this->access_key == null){
            $generator = new SecureRandom();
            $this->access_key=sha1($generator->nextBytes(32));
            $this->access_secret=base64_encode($generator->nextBytes(32));
        }
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
    private $active_group = 0;

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

//    /**
//     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\LimitCount", mappedBy="user", cascade={"remove"})
//     * @Expose
//     */
//    private $limit_counts;

//    /**
//     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\UserWallet", mappedBy="user", cascade={"remove"})
//     * @Expose
//     */
//    private $wallets;

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

//    /**
//     * @ORM\Column(type="string")
//     * @Expose
//     */
//    private $default_currency;

    /**
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $prefix;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $phone;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $gcm_group_key;

//    /**
//     * @ORM\Column(type="string", length=1000)
//     * @Expose
//     */
//    private $services_list;
//
//    /**
//     * @ORM\Column(type="string", length=1000)
//     * @Expose
//     */
//    private $methods_list;

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

//    /**
//     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Balance", mappedBy="user", cascade={"remove"})
//     */
//    private $balance;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\CashInTokens", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $cash_in_tokens;

//    /**
//     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Client", mappedBy="user", cascade={"remove"})
//     * @Expose
//     */
//    private $clients;

//    /**
//     * @Expose
//     */
//    private $allowed_services = array();
//
//    /**
//     * @Expose
//     */
//    private $allowed_methods = array();

    /**
     * @Expose
     */
    private $group_data = array();

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\TierValidations", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $tier_validations;

    /**
     * Random string sent to the user email address in order to recover the password
     *
     * @ORM\Column(type="string", nullable=true)
     * @Exclude
     */
    private $recover_password_token;

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

//    /**
//     * @param array $allowed_services
//     */
//    public function setAllowedServices($allowed_services)
//    {
//        $this->allowed_services = $allowed_services;
//    }

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

//    /**
//     * @return mixed
//     */
//    public function getLimitCount()
//    {
//        return $this->limit_counts;
//    }
//
//    /**
//     * @param mixed $limit_count
//     */
//    public function setLimitCount($limit_count)
//    {
//        $this->limit_counts = $limit_count;
//    }
//
//    /**
//     * @return mixed
//     */
//    public function getWallets()
//    {
//        return $this->wallets;
//    }
//
//    /**
//     * @param mixed $wallets
//     */
//    public function setWallets($wallets)
//    {
//        $this->wallets = $wallets;
//    }
//
//    /**
//     * @return mixed
//     */
//    public function getDefaultCurrency()
//    {
//        return $this->default_currency;
//    }
//
//    /**
//     * @param mixed $default_currency
//     */
//    public function setDefaultCurrency($default_currency)
//    {
//        $this->default_currency = $default_currency;
//    }

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

//    /**
//     * @return mixed
//     */
//    public function getBalance()
//    {
//        return $this->balance;
//    }
//
//    /**
//     * @param mixed $balance
//     */
//    public function setBalance($balance)
//    {
//        $this->balance = $balance;
//    }

//    /**
//     * @return mixed
//     */
//    public function getServicesList()
//    {
//        return json_decode($this->services_list);
//    }
//
//    /**
//     * @param mixed $services_list
//     */
//    public function setServicesList($services_list)
//    {
//        $this->services_list = json_encode($services_list);
//    }
//
//    /**
//     * @param mixed $cname
//     */
//    public function addService($cname){
//        $new = array($cname);
//        $merge = array_merge($this->services_list, $new);
//        $result = array_unique($merge, SORT_REGULAR);
//        $this->services_list = json_encode($result);
//    }
//
//    /**
//     * @param mixed $cname
//     */
//    public function removeService($cname){
//        $result = array_diff(json_decode($this->services_list), array($cname));
//        $this->services_list = json_encode(array_values($result));
//    }

    /**
     * @return mixed
     */
    public function getCashInTokens()
    {
        return $this->cash_in_tokens;
    }

    /**
     * @param mixed $cash_in_tokens
     */
    public function setCashInTokens($cash_in_tokens)
    {
        $this->cash_in_tokens = $cash_in_tokens;
    }

    /**
     * @param array $group_data
     */
    public function setGroupData($group_data)
    {
        $this->group_data = $group_data;
    }

//    /**
//     * @return mixed
//     */
//    public function getClients()
//    {
//        return $this->clients;
//    }
//
//    /**
//     * @param mixed $clients
//     */
//    public function setClients($clients)
//    {
//        $this->clients = $clients;
//    }

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

//    /**
//     * @param mixed $allowed_methods
//     */
//    public function setAllowedMethods($allowed_methods)
//    {
//        $this->allowed_methods = $allowed_methods;
//    }
//
//    /**
//     * @return mixed
//     */
//    public function getMethodsList()
//    {
//        return json_decode($this->methods_list);
//    }
//
//    /**
//     * @param mixed $methods_list
//     */
//    public function setMethodsList($methods_list)
//    {
//        $this->methods_list = json_encode($methods_list);
//    }
//
//    /**
//     * @param mixed $cname
//     */
//    public function addMethod($cname){
//        $new = array($cname);
//        $merge = array_merge($this->methods_list, $new);
//        $result = array_unique($merge, SORT_REGULAR);
//        $this->methods_list = json_encode($result);
//    }
//
//    /**
//     * @param mixed $cname
//     */
//    public function removeMethod($cname){
//        $result = array_diff(json_decode($this->methods_list), array($cname));
//        $this->methods_list = json_encode(array_values($result));
//    }

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
     * @return mixed
     */
    public function getActiveGroup()
    {
        if($this->active_group == 0){
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

    public function getAdminView(){
        unset($this->btc_addresses);
        unset($this->access_secret);
        unset($this->access_key);
        unset($this->devices);
        unset ( $this->gcm_group_key);
        unset($this->twoFactorAuthentication);
        unset ($this->cash_in_tokens);
        unset($this->group_data);
        return $this;
    }
}