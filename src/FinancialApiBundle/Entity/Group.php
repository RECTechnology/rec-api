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
class Group extends BaseGroup implements Uploadable
{

    const SERIALIZATION_GROUPS_PUBLIC  =                                     ['public'];
    const SERIALIZATION_GROUPS_USER    =                             ['user', 'public'];
    const SERIALIZATION_GROUPS_MANAGER =                  ['manager', 'user', 'public'];
    const SERIALIZATION_GROUPS_ADMIN   =         ['admin', 'manager', 'user', 'public'];
    const SERIALIZATION_GROUPS_ROOT    = ['root', 'admin', 'manager', 'user', 'public'];

    const ACCOUNT_TYPE_PRIVATE = 'PRIVATE';
    const ACCOUNT_TYPE_ORGANIZATION = 'COMPANY';
    const ACCOUNT_SUBTYPE_NORMAL = 'NORMAL';
    const ACCOUNT_SUBTYPE_BMINCOME = 'BMINCOME';
    const ACCOUNT_SUBTYPE_WHOLESALE = 'WHOLESALE';
    const ACCOUNT_SUBTYPE_RETAILER = 'RETAILER';

    const ACCESS_STATE_NOT_GRANTED = 'not_granted';
    const ACCESS_STATE_PENDING = 'pending';
    const ACCESS_STATE_GRANTED = 'granted';

    /**
     * Group constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->limit_counts = new ArrayCollection();
        $this->wallets = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->consuming_products = new ArrayCollection();
        $this->producing_products = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->ibans = new ArrayCollection();
        $this->company_token = uniqid();
        $this->on_map = 1;
        $this->campaigns = new ArrayCollection();
        $this->created = new \DateTime();
        $this->badges = new ArrayCollection();

        if ($this->access_key == null) {
            $this->access_key = sha1(random_bytes(32));
            $this->key_chain = sha1(random_bytes(32));
            $this->access_secret = base64_encode(random_bytes(32));
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
     * @Serializer\MaxDepth(1)
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\User")
     * @Serializer\Groups({"manager"})
     * @Serializer\MaxDepth(1)
     */
    private $kyc_manager;

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"user"})
     */
    private $company_image = "";

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"public"})
     */
    private $rec_address;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $lw_balance;

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
     * @ORM\Column(type="text")
     * @Serializer\Groups({"admin"})
     */
    private $nft_wallet = '';

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"admin"})
     */
    private $nft_wallet_pk = '';


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
     * @Serializer\MaxDepth(1)
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
     * @Assert\Regex(
     *     pattern="/^[0-9]{2}$/",
     *     message="Invalid prefix format"
     * )
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
     * @Assert\Range(max="90", min="-90", invalidMessage="Bad value for latitude (allowed float [-90, 90])")
     * @Serializer\Groups({"public"})
     */
    private $latitude = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\Type(type="double", message="Invalid longitude {{value}}")
     * @Assert\Range(max="90", min="-90", invalidMessage="Bad value for longitude (allowed float [-90, 90])")
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
     * @Serializer\MaxDepth(1)
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
     * @Serializer\Groups({"public"})
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
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Document", mappedBy="account", cascade={"remove"})
     * @Serializer\Groups({"user"})
     */
    private $documents;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Iban", mappedBy="account", cascade={"remove"})
     * @Serializer\Groups({"user"})
     */
    private $ibans;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $tier = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Tier")
     * @Serializer\Groups({"user"})
     */
    private $level;

    /**
     * @ORM\OneToOne(targetEntity="App\FinancialApiBundle\Entity\Pos", mappedBy="account")
     * @Serializer\Groups({"user"})
     */
    private $pos;

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
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Campaign", inversedBy="accounts")
     * @Serializer\MaxDepth(3)
     * @Serializer\Groups({"public"})
     */
    private $campaigns;


    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"user"})
     */
    private $redeemable_amount = 0;

    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"user"})
     */
    private $rewarded_amount = 0;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose
     * @Serializer\Groups({"manager"})
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Groups({"manager"})
     */
    private $disabled_at;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Serializer\Expose
     * @Serializer\Groups({"user"})
     */
    private $rezero_b2b_username;

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice({Group::ACCESS_STATE_NOT_GRANTED, Group::ACCESS_STATE_PENDING, Group::ACCESS_STATE_GRANTED})
     * @Serializer\Expose
     * @Serializer\Groups({"user"})
     */
    private $rezero_b2b_access = Group::ACCESS_STATE_NOT_GRANTED;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Serializer\Expose
     * @Serializer\Groups({"admin"})
     */
    private $rezero_b2b_api_key;

    /**
     * @ORM\Column(type="integer", unique=true, nullable=true)
     * @Serializer\Expose
     * @Serializer\Groups({"admin"})
     */
    private $rezero_b2b_user_id;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Badge",
     *     mappedBy="accounts",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"public"})
     */
    private $badges;

    /**
     * @return mixed
     */
    public function getRedeemableAmount()
    {
        return $this->redeemable_amount;
    }

    /**
     * @param mixed $redeemable_amount
     */
    public function setRedeemableAmount($redeemable_amount)
    {
        $this->redeemable_amount = $redeemable_amount;
    }

    /**
     * @return mixed
     */
    public function getRewardedAmount()
    {
        return $this->rewarded_amount;
    }

    /**
     * @param mixed
     */
    public function setRewardedAmount($rewarded_amount)
    {
        $this->rewarded_amount = $rewarded_amount;
    }



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

    public function getCommission($service)
    {
        $commissions = $this->getCommissions();
        foreach ($commissions as $fee) {
            if ($fee->getServiceName() == $service) {
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
        foreach ($wallets as $wallet) {
            if ($wallet->getCurrency() == strtoupper($currency)) {
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
     * @return string
     */
    public function getNftWallet(): string
    {
        return $this->nft_wallet;
    }

    /**
     * @param string $nft_wallet
     */
    public function setNftWallet(string $nft_wallet): void
    {
        $this->nft_wallet = $nft_wallet;
    }

    /**
     * @return string
     */
    public function getNftWalletPk(): string
    {
        return $this->nft_wallet_pk;
    }

    /**
     * @param string $nft_wallet_pk
     */
    public function setNftWalletPk(string $nft_wallet_pk): void
    {
        $this->nft_wallet_pk = $nft_wallet_pk;
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
    public function addMethod($cname)
    {
        $new = array($cname);
        $merge = array_merge($this->methods_list, $new);
        $result = array_unique($merge, SORT_REGULAR);
        $this->methods_list = json_encode($result);
    }

    /**
     * @param mixed $cname
     */
    public function removeMethod($cname)
    {
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

    public function getAdminView()
    {
        //        unset($this->access_key);
        //        unset($this->access_secret);
        //        unset ($this->kyc_manager);
        //        unset ($this->limit_counts);
        //        unset ($this->cash_in_tokens);
        return $this;
    }

    public function getUserView()
    {
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
        if (strlen($country) != 3) {
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
        if($this->active == true and $active == false){
            $this->disabled_at = new \DateTime();
        }
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
        if ($this->producing_products->contains($product)) {
            throw new PreconditionFailedException("Product already related to this Account");
        }
        $this->producing_products[] = $product;
        if ($recursive) $product->addProducingBy($this, false);
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delProducingProduct(ProductKind $product, $recursive = true): void
    {
        if (!$this->producing_products->contains($product)) {
            throw new PreconditionFailedException("Product not related to this Account");
        }
        $this->producing_products->removeElement($product);
        if($recursive) $product->delProducingBy($this, false);
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
        if ($this->consuming_products->contains($product)) {
            throw new PreconditionFailedException("Product already related to this Account");
        }
        $this->consuming_products[] = $product;
        if ($recursive) $product->addConsumingBy($this, false);
    }

    /**
     * @param mixed $product
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delConsumingProduct(ProductKind $product, $recursive = true): void
    {
        if (!in_array($product, $this->consuming_products->getValues())) {
            throw new PreconditionFailedException("Product not related to this Account");
        }
        $this->consuming_products->removeElement($product);
        if($recursive) $product->delConsumingBy($this, false);
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
        if ($this->activities->contains($activity)) {
            throw new PreconditionFailedException("Activity already related to this Account");
        }
        $this->activities[] = $activity;
        if ($recursive) $activity->addAccount($this, false);
    }

    /**
     * @param Activity $activity
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delActivity(Activity $activity, $recursive = true)
    {
        if (!$this->activities->contains($activity)) {
            throw new PreconditionFailedException("Activity not related to this Account");
        }
        $this->activities->removeElement($activity);
        if ($this->activity_main == $activity) $this->activity_main = null;
        if($recursive) $activity->delAccount($this, false);
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

    /**
     * @return mixed
     */
    public function getLwBalance()
    {
        return $this->lw_balance;
    }

    /**
     * @param mixed $lw_balance
     */
    public function setLwBalance($lw_balance): void
    {
        $this->lw_balance = $lw_balance;
    }

    /**
     * @return mixed
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param mixed $documents
     */
    public function setDocuments($documents): void
    {
        $this->documents = $documents;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     */
    public function setLevel($level): void
    {
        $this->level = $level;
    }

    /**
     * @return mixed
     */
    public function getIbans()
    {
        return $this->ibans;
    }

    /**
     * @param mixed $ibans
     */
    public function setIbans($ibans): void
    {
        $this->ibans = $ibans;
    }

    /**
     * Get the value of pos
     */ 
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * Set the value of pos
     *
     * @return  self
     */ 
    public function setPos($pos)
    {
        $this->pos = $pos;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCampaigns()
    {
        return $this->campaigns;
    }

    /**
     * @param mixed $campaigns
     */
    public function setCampaigns($campaigns): void
    {
        $this->campaigns = $campaigns;
    }

    /**
     * @param Campaign $campaign
     * @throws PreconditionFailedException
     */
    public function delCampaign(Campaign $campaign)
    {
        if (!$this->campaigns->contains($campaign)) {
            throw new PreconditionFailedException("Campaign not related to this Account");
        }
        $this->campaigns->removeElement($campaign);
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
    public function getDisabledAt()
    {
        return $this->disabled_at;
    }

    /**
     * @param mixed $disabled_at
     */
    public function setDisabledAt($disabled_at): void
    {
        $this->disabled_at = $disabled_at;
    }

    /**
     * @return mixed
     */
    public function getRezeroB2bUsername()
    {
        return $this->rezero_b2b_username;
    }

    /**
     * @param mixed $rezero_b2b_username
     */
    public function setRezeroB2bUsername($rezero_b2b_username): void
    {
        $this->rezero_b2b_username = $rezero_b2b_username;
    }

    /**
     * @return string
     */
    public function getRezeroB2bAccess(): string
    {
        return $this->rezero_b2b_access;
    }

    /**
     * @param string $rezero_b2b_access
     */
    public function setRezeroB2bAccess(string $rezero_b2b_access): void
    {
        $this->rezero_b2b_access = $rezero_b2b_access;
    }

    /**
     * @return mixed
     */
    public function getRezeroB2bApiKey()
    {
        return $this->rezero_b2b_api_key;
    }

    /**
     * @param mixed $rezero_b2b_api_key
     */
    public function setRezeroB2bApiKey($rezero_b2b_api_key): void
    {
        $this->rezero_b2b_api_key = $rezero_b2b_api_key;
    }

    /**
     * @return mixed
     */
    public function getRezeroB2bUserId()
    {
        return $this->rezero_b2b_user_id;
    }

    /**
     * @param mixed $rezero_b2b_user_id
     */
    public function setRezeroB2bUserId($rezero_b2b_user_id): void
    {
        $this->rezero_b2b_user_id = $rezero_b2b_user_id;
    }

    /**
     * @return mixed
     */
    public function getBadges()
    {
        return $this->badges;
    }

    /**
     * @param Badge $badge
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function addBadge(Badge $badge, $recursive = true): void
    {
        if ($this->badges->contains($badge)) {
            throw new PreconditionFailedException("Badge already related to this Account");
        }
        $this->badges[] = $badge;
        if ($recursive) $badge->addAccount($this, false);
    }

    /**
     * @param badge $baddge
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function delBadge(Badge $badge, $recursive = true)
    {
        if (!$this->badges->contains($badge)) {
            throw new PreconditionFailedException("Badge not related to this Account");
        }
        $this->badges->removeElement($badge);
        if($recursive) $badge->delAccount($this, false);
    }
}
