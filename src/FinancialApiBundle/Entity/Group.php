<?php

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Exception\AppLogicException;
use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\Group as BaseGroup;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_group")
 * @Serializer\ExclusionPolicy("none")
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
class Group extends BaseGroup implements EntityWithUploadableFields {

    const SERIALIZATION_GROUPS_PUBLIC  =                                     ['public'];
    const SERIALIZATION_GROUPS_USER    =                             ['user', 'public'];
    const SERIALIZATION_GROUPS_MANAGER =                  ['manager', 'user', 'public'];
    const SERIALIZATION_GROUPS_ADMIN   =         ['admin', 'manager', 'user', 'public'];
    const SERIALIZATION_GROUPS_ROOT    = ['root', 'admin', 'manager', 'user', 'public'];

    /**
     * Group constructor.
     * @throws \Exception
     */
    public function __construct() {
        $this->groups = new ArrayCollection();
        $this->limit_counts = new ArrayCollection();
        $this->wallets = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->consuming_products = new ArrayCollection();
        $this->producing_products = new ArrayCollection();
        $this->company_token = uniqid();
        $this->on_map = 1;

        if($this->access_key == null){
            $this->access_key=sha1(random_bytes(32));
            $this->key_chain=sha1(random_bytes(32));
            $this->access_secret=base64_encode(random_bytes(32));
        }
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"public"})
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\UserGroup", mappedBy="group", cascade={"remove"})
     * @Serializer\Exclude
     * @Serializer\Groups({"manager"})
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\User")
     * @Serializer\Groups({"manager"})
     */
    private $kyc_manager;

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"manager"})
     */
    private $company_image = "";

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"user"})
     */
    private $rec_address;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Category")
     * @Serializer\Groups({"public"})
     */
    private $category;

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $offered_products = "";

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $needed_products = "";

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\LimitDefinition", mappedBy="group", cascade={"remove"})
     * @Serializer\Groups({"user"})
     *
     */
    private $limits;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\ServiceFee", mappedBy="group", cascade={"remove"})
     * @Serializer\Groups({"user"})
     *
     */
    private $commissions;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\UserWallet", mappedBy="group", cascade={"remove"})
     * @Serializer\Groups({"user"})
     */
    private $wallets;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\LimitCount", mappedBy="group", cascade={"remove"})
     * @Serializer\Groups({"user"})
     */
    private $limit_counts;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $access_key;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Exclude
     * @Serializer\Groups({"user"})
     */
    private $key_chain;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Exclude
     * @Serializer\Groups({"user"})
     */
    private $is_public_profile = false;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $access_secret;

    /**
     * @ORM\Column(type="string", length=1000)
     * @Serializer\Exclude
     * @Serializer\Groups({"user"})
     */
    private $methods_list;

    /**
     * @Serializer\Groups({"user"})
     */
    private $allowed_methods = array();

    /**
     * @Serializer\Groups({"user"})
     */
    private $limit_configuration = array();

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Balance", mappedBy="group", cascade={"remove"})
     * @Serializer\Exclude
     * @Serializer\Groups({"user"})
     */
    private $balance;


    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Offer", mappedBy="company", cascade={"remove"})
     * @Serializer\Groups({"public"})
     */
    private $offers;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Client", mappedBy="group", cascade={"remove"})
     * @Serializer\Exclude
     * @Serializer\Groups({"user"})
     */
    private $clients;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Activity",
     *     mappedBy="accounts",
     *     fetch="EXTRA_LAZY"
     * )
     * @Assert\Count(max="10")
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"public"})
     */
    private $activities;


    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Activity")
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"public"})
     */
    private $activity_main;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\ProductKind",
     *     mappedBy="producing_by",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"public"})
     */
    private $producing_products;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\ProductKind",
     *     mappedBy="consuming_by",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"public"})
     */
    private $consuming_products;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $cif;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $prefix = '';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $phone = '';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $zip = '';

    /**
     * @ORM\Column(type="string")
     * @Assert\Email()
     * @Serializer\Groups({"manager"})
     */
    private $email = '';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $city = '';

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(min="3", max="3", exactMessage="Country must be ISO-3")
     * @Serializer\Groups({"public"})
     */
    private $country = '';

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\Type(type="double", message="Invalid latitude {{value}}")
     * @Serializer\Groups({"public"})
     */
    private $latitude = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\Type(type="double", message="Invalid longitude {{value}}")
     * @Serializer\Groups({"public"})
     */
    private $longitude = null;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"public"})
     */
    private $fixed_location = false;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $web = '';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $address_number = '';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $neighborhood = "";

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Neighbourhood", inversedBy="accounts")
     * @Serializer\Groups({"public"})
     */
    private $neighbourhood;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $association = "";

    /**
     * @ORM\Column(type="string", length=300)
     * @Serializer\Groups({"public"})
     */
    private $observations = "";

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $street = '';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $street_type = "";

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $comment = '';

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"user"})
     */
    private $type = '';

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"admin"})
     */
    private $lemon_id = '';

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $subtype = '';

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $description = '';

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $schedule = '';

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $public_image = "";

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"admin"})
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\CashInTokens", mappedBy="company", cascade={"remove"})
     * @Serializer\Groups({"admin"})
     */
    private $cash_in_tokens;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $tier = 0;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $company_token;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"admin"})
     */
    private $on_map;

    /**
     * @return string
     * @Serializer\VirtualProperty("name")
     * @Serializer\Type("string")
     * @Serializer\Groups({"public"})
     */
    public function getName()
    {
        return parent::getName();
    }

    /**
     * @return integer
     * @Serializer\VirtualProperty("offer_count")
     * @Serializer\Type("integer")
     * @Serializer\Groups({"public"})
     */
    public function getOfferCount()
    {
        return $this->getOffers()->count();
    }


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
        $category->addAccount($this);
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
    public function setCountry($country){
        if(strlen($country)!=3){
            throw new AppLogicException('Country must be ISO-3');
        }
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
    public function getFixedLocation()
    {
        return $this->fixed_location;
    }

    /**
     * @param mixed $fixed_location
     */
    public function setFixedLocation($fixed_location)
    {
        $this->fixed_location = $fixed_location;
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
    public function getLemonId()
    {
        return $this->lemon_id;
    }

    /**
     * @param mixed $lemon_id
     */
    public function setLemonId($lemon_id)
    {
        $this->lemon_id = $lemon_id;
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
    public function getNeighborhood()
    {
        return $this->neighborhood;
    }

    /**
     * @param mixed $neighborhood
     */
    public function setNeighborhood($neighborhood)
    {
        $this->neighborhood = $neighborhood;
    }

    /**
     * @return mixed
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * @param mixed $association
     */
    public function setAssociation($association)
    {
        $this->association = $association;
    }

    /**
     * @return mixed
     */
    public function getObservations()
    {
        return $this->observations;
    }

    /**
     * @param mixed $observations
     */
    public function setObservations($observations)
    {
        $this->observations = $observations;
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


    /**
     * @return mixed
     */
    public function getIsPublicProfile()
    {
        return $this->is_public_profile;
    }

    /**
     * @param mixed $is_public_profile
     */
    public function setIsPublicProfile($is_public_profile)
    {
        $this->is_public_profile = $is_public_profile;
    }

    /**
     * @return mixed
     */
    public function getOffers()
    {
        return $this->offers;
    }

    /**
     * @param mixed $offers
     */
    public function setOffers($offers)
    {
        $this->offers = $offers;
    }

    /**
     * @param mixed $on_map
     */
    public function setOnMap($on_map)
    {
        $this->on_map = $on_map;
    }

    /**
     * @return mixed
     */
    public function getOnMap()
    {
        return $this->on_map;
    }

    function getUploadableFields()
    {
        return [
            'company_image' => UploadManager::$FILTER_IMAGES,
            'public_image' => UploadManager::$FILTER_IMAGES
        ];
    }

    /**
     * @return mixed
     */
    public function getNeighbourhood()
    {
        return $this->neighbourhood;
    }

    /**
     * @param mixed $neighbourhood
     */
    public function setNeighbourhood($neighbourhood): void
    {
        $this->neighbourhood = $neighbourhood;
    }

    /**
     * @return mixed
     */
    public function getProducingProducts()
    {
        return $this->producing_products;
    }

    /**
     * @param ProductKind $product
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function addProducingProduct(ProductKind $product, $recursive = true): void
    {
        if($this->producing_products->contains($product)){
            throw new PreconditionFailedException("Product already related to this Account");
        }
        $this->producing_products []= $product;
        if($recursive) $product->addProducingBy($this, false);
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delProducingProduct(ProductKind $product, $recursive = true): void
    {
        if(!$this->producing_products->contains($product)){
            throw new PreconditionFailedException("Product not related to this Account");
        }
        $this->producing_products->removeElement($product);
        //if($recursive) $product->delProducingBy($this, false);
    }

    /**
     * @return mixed
     */
    public function getConsumingProducts()
    {
        return $this->consuming_products;
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function addConsumingProduct(ProductKind $product, $recursive = true): void
    {
        if($this->consuming_products->contains($product)){
            throw new PreconditionFailedException("Product already related to this Account");
        }
        $this->consuming_products []= $product;
        if($recursive) $product->addConsumingBy($this, false);
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delConsumingProduct(ProductKind $product, $recursive = true): void
    {
        if(!$this->consuming_products->contains($product)){
            throw new PreconditionFailedException("Product not related to this Account");
        }
        $this->consuming_products->removeElement($product);
        //if($recursive) $product->delConsumingBy($this, false);
    }

    /**
     * @return mixed
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * @param Activity $activity
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function addActivity(Activity $activity, $recursive = true): void
    {
        if($this->activities->contains($activity)){
            throw new PreconditionFailedException("Activity already related to this Account");
        }
        $this->activities []= $activity;
        if($recursive) $activity->addAccount($this, false);
    }

    /**
     * @param Activity $activity
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delActivity(Activity $activity, $recursive = true) {
        if(!$this->activities->contains($activity)){
            throw new PreconditionFailedException("Activity not related to this Account");
        }
        $this->activities->removeElement($activity);
        //if($recursive) $activity->delAccount($this, false);
    }

    /**
     * @return mixed
     */
    public function getActivityMain()
    {
        return $this->activity_main;
    }

    /**
     * @param mixed $activity_main
     */
    public function setActivityMain($activity_main): void
    {
        $this->activity_main = $activity_main;
    }
}