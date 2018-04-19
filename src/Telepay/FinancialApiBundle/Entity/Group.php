<?php

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\Group as BaseGroup;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Security\Core\Util\SecureRandom;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_group")
 * @ExclusionPolicy("none")
 *
 * @ORM\AttributeOverrides({
 *     @ORM\AttributeOverride(name="name",
 *         column=@ORM\Column(
 *             name="name",
 *             type="string",
 *             length=255,
 *             unique=false
 *         )
 *     )
 * })
 */
class Group extends BaseGroup
{

    public function __construct() {
        $this->groups = new ArrayCollection();
        $this->limit_counts = new ArrayCollection();
        $this->wallets = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->company_token = uniqid();

        if($this->access_key == null){
            $generator = new SecureRandom();
            $this->access_key=sha1($generator->nextBytes(32));
            $this->key_chain=sha1($generator->nextBytes(32));
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
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\UserGroup", mappedBy="group", cascade={"remove"})
     * @Exclude
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    private $kyc_manager;

    /**
     * @ORM\Column(type="text")
     * @Expose
     */
    private $company_image = "";

    /**
     * @ORM\Column(type="text")
     * @Expose
     */
    private $rec_address;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Category")
     */
    private $category;

    /**
     * @ORM\Column(type="text")
     * @Expose
     */
    private $offered_products = "";

    /**
     * @ORM\Column(type="text")
     * @Expose
     */
    private $needed_products = "";

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\LimitDefinition", mappedBy="group", cascade={"remove"})
     *
     */
    private $limits;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\ServiceFee", mappedBy="group", cascade={"remove"})
     *
     */
    private $commissions;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\UserWallet", mappedBy="group", cascade={"remove"})
     */
    private $wallets;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\LimitCount", mappedBy="group", cascade={"remove"})
     */
    private $limit_counts;

    /**
     * @ORM\Column(type="string")
     */
    private $access_key;

    /**
     * @ORM\Column(type="string")
     * @Exclude
     */
    private $key_chain;

    /**
     * @ORM\Column(type="string")
     */
    private $access_secret;

    /**
     * @ORM\Column(type="string", length=1000)
     * @Exclude
     */
    private $methods_list;

    /**
     * @Expose
     */
    private $allowed_methods = array();

    /**
     * @Expose
     */
    private $limit_configuration = array();

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Balance", mappedBy="group", cascade={"remove"})
     * @Exclude
     */
    private $balance;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Client", mappedBy="group", cascade={"remove"})
     * @Exclude
     */
    private $clients;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $cif;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $prefix = '';

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $phone = '';

    /**
     * @ORM\Column(type="string")
     */
    private $zip = '';

    /**
     * @ORM\Column(type="string")
     */
    private $email = '';

    /**
     * @ORM\Column(type="string")
     */
    private $city = '';

    /**
     * @ORM\Column(type="string")
     */
    private $country = '';

    /**
     * @ORM\Column(type="float")
     */
    private $latitude = '';

    /**
     * @ORM\Column(type="float")
     */
    private $longitude = '';

    /**
     * @ORM\Column(type="string")
     */
    private $web = '';

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $address_number = '';

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $street = '';

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $street_type = "";

    /**
     * @ORM\Column(type="text")
     */
    private $comment = '';

    /**
     * @ORM\Column(type="text")
     */
    private $type = '';

    /**
     * @ORM\Column(type="text")
     */
    private $subtype = '';

    /**
     * @ORM\Column(type="text")
     */
    private $description = '';

    /**
     * @ORM\Column(type="text")
     */
    private $schedule = '';

    /**
     * @ORM\Column(type="text")
     * @Expose
     */
    private $public_image = "";

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\CashInTokens", mappedBy="company", cascade={"remove"})
     */
    private $cash_in_tokens;

    /**
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $tier = 0;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $company_token;

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param mixed $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getLimits()
    {
        return $this->limits;
    }

    /**
     * @param mixed $limits
     */
    public function setLimits($limits)
    {
        $this->limits = $limits;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getOfferedProducts()
    {
        return $this->offered_products;
    }

    /**
     * @param mixed $offered_products
     */
    public function setOfferedProducts($offered_products)
    {
        $this->offered_products = $offered_products;
    }

    /**
     * @return mixed
     */
    public function getNeededProducts()
    {
        return $this->needed_products;
    }

    /**
     * @param mixed $needed_products
     */
    public function setNeededProducts($needed_products)
    {
        $this->needed_products = $needed_products;
    }

    /**
     * @return mixed
     */
    public function getCommissions()
    {
        return $this->commissions;
    }

    public function getCommission($service){
        $commissions = $this->getCommissions();
        foreach($commissions as $fee){
            if($fee->getServiceName() == $service){
                return $fee;
            }
        }
    }

    /**
     * @param mixed $commissions
     */
    public function setCommissions($commissions)
    {
        $this->commissions = $commissions;
    }

    /**
     * @return mixed
     */
    public function getWallets()
    {
        return $this->wallets;
    }

    public function getWallet($currency)
    {
        $wallets = $this->getWallets();
        foreach($wallets as $wallet){
            if($wallet->getCurrency() == strtoupper($currency)){
                return $wallet;
            }
        }
    }

    /**
     * @param mixed $wallets
     */
    public function setWallets($wallets)
    {
        $this->wallets = $wallets;
    }

    /**
     * @return mixed
     */
    public function getLimitCounts()
    {
        return $this->limit_counts;
    }

    /**
     * @param mixed $limit_counts
     */
    public function setLimitCounts($limit_counts)
    {
        $this->limit_counts = $limit_counts;
    }

    /**
     * @return mixed
     */
    public function getAccessKey()
    {
        return $this->access_key;
    }

    /**
     * @return mixed
     */
    public function getAccessSecret()
    {
        return $this->access_secret;
    }

    /**
     * @return mixed
     */
    public function getKeyChain()
    {
        return $this->key_chain;
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
     * @param mixed $key_chain
     */
    public function setKeyChain($key_chain)
    {
        $this->key_chain = $key_chain;
    }

    /**
     * @param mixed $allowed_methods
     */
    public function setAllowedMethods($allowed_methods)
    {
        $this->allowed_methods = $allowed_methods;
    }

    /**
     * @return mixed
     */
    public function getMethodsList()
    {
        return json_decode($this->methods_list);
    }

    /**
     * @param mixed $methods_list
     */
    public function setMethodsList($methods_list)
    {
        $this->methods_list = json_encode($methods_list);
    }

    /**
     * @param mixed $cname
     */
    public function addMethod($cname){
        $new = array($cname);
        $merge = array_merge($this->methods_list, $new);
        $result = array_unique($merge, SORT_REGULAR);
        $this->methods_list = json_encode($result);
    }

    /**
     * @param mixed $cname
     */
    public function removeMethod($cname){
        $result = array_diff(json_decode($this->methods_list), array($cname));
        $this->methods_list = json_encode(array_values($result));
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param mixed $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * @return mixed
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @param mixed $clients
     */
    public function setClients($clients)
    {
        $this->clients = $clients;
    }

    public function getAdminView(){
//        unset($this->access_key);
//        unset($this->access_secret);
//        unset ($this->kyc_manager);
//        unset ($this->limit_counts);
//        unset ($this->cash_in_tokens);
        return $this;
    }

    public function getUserView(){
//        unset($this->limits);
//        unset($this->commissions);
//        unset($this->wallets);
//        unset($this->limit_counts);
//        unset($this->methods_list);
//        unset($this->allowed_methods);
//        unset($this->comment);
//        unset($this->cash_in_tokens);
//        unset($this->cif);
//        unset($this->prefix);
//        unset($this->address_number);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCif()
    {
        return $this->cif;
    }

    /**
     * @param mixed $cif
     */
    public function setCif($cif)
    {
        $this->cif = $cif;
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param mixed $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param mixed $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return mixed
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * @param mixed $web
     */
    public function setWeb($web)
    {
        $this->web = $web;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param mixed $subtype
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
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
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @param mixed $schedule
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * @return mixed
     */
    public function getPublicImage()
    {
        return $this->public_image;
    }

    /**
     * @param mixed $public_image
     */
    public function setPublicImage($public_image)
    {
        $this->public_image = $public_image;
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
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param mixed $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param mixed $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return mixed
     */
    public function getStreetType()
    {
        return $this->street_type;
    }

    /**
     * @param mixed $street_type
     */
    public function setStreetType($street_type)
    {
        $this->street_type = $street_type;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function getAdresses()
    {
        return $this->adresses;
    }

    /**
     * @param mixed $adresses
     */
    public function setAdresses($adresses)
    {
        $this->adresses = $adresses;
    }

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
     * @return mixed
     */
    public function getAddressNumber()
    {
        return $this->address_number;
    }

    /**
     * @param mixed $address_number
     */
    public function setAddressNumber($address_number)
    {
        $this->address_number = $address_number;
    }

    /**
     * @return mixed
     */
    public function getKycManager()
    {
        return $this->kyc_manager;
    }

    /**
     * @param mixed $kyc_manager
     */
    public function setKycManager($kyc_manager)
    {
        $this->kyc_manager = $kyc_manager;
    }

    /**
     * @return mixed
     */
    public function getTier()
    {
        return $this->tier;
    }

    /**
     * @param mixed $tier
     */
    public function setTier($tier)
    {
        $this->tier = $tier;
    }

    /**
     * @return mixed
     */
    public function getCompanyToken()
    {
        return $this->company_token;
    }

    /**
     * @param mixed $company_token
     */
    public function setCompanyToken($company_token)
    {
        $this->company_token = $company_token;
    }

    /**
     * @return mixed
     */
    public function getCompanyImage()
    {
        return $this->company_image;
    }

    /**
     * @param mixed $company_image
     */
    public function setCompanyImage($company_image)
    {
        $this->company_image = $company_image;
    }


    /**
     * @return mixed
     */
    public function getRecAddress()
    {
        return $this->rec_address;
    }

    /**
     * @param mixed $rec_address
     */
    public function setRecAddress($rec_address)
    {
        $this->rec_address = $rec_address;
    }

    /**
     * @param mixed $limit_configuration
     */
    public function setLimitConfiguration($limit_configuration)
    {
        $this->limit_configuration = $limit_configuration;
    }



}