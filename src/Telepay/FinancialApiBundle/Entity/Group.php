<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

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
 */
class Group extends BaseGroup
{

    public function __construct() {
        $this->groups = new ArrayCollection();
        $this->limit_counts = new ArrayCollection();
        $this->wallets = new ArrayCollection();
        $this->clients = new ArrayCollection();

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
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\UserGroup", mappedBy="groups", cascade={"remove"})
     * @Exclude
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     * @Exclude
     */
    private $creator;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     * @Exclude
     */
    private $group_creator;

    /**
     * @ORM\Column(type="text")
     * @Expose
     */
    private $base64_image = "";

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
     */
    private $access_secret;

    /**
     * @ORM\Column(type="string")
     */
    private $default_currency;

    /**
     * @ORM\Column(type="string", length=1000)
     */
    private $methods_list;

    /**
     * @Expose
     */
    private $allowed_methods = array();

    /**
     * @Expose
     */
    private $group_creator_data = array();

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
    private $cif = '';

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
    private $town = '';

    /**
     * @ORM\Column(type="text")
     */
    private $comment = '';

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
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param mixed $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
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
    public function getCommissions()
    {
        return $this->commissions;
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
    public function getDefaultCurrency()
    {
        return $this->default_currency;
    }

    /**
     * @param mixed $default_currency
     */
    public function setDefaultCurrency($default_currency)
    {
        $this->default_currency = $default_currency;
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

    /**
     * @return mixed
     */
    public function getGroupCreator()
    {
        return $this->group_creator;
    }

    /**
     * @param mixed $group_creator
     */
    public function setGroupCreator($group_creator)
    {
        $this->group_creator = $group_creator;
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

    public function getAdminView(){
        unset($this->base64_image);
        unset($this->access_key);
        unset($this->access_secret);
        unset ($this->default_currency);
        $users = $this->users;
//        foreach($users as $user){
//            $user = $user->getAdminView();
//        }
        $this->users = $users;

        return $this;
    }

    public function getUserView(){
        unset($this->access_key);
        unset($this->access_secret);
        unset($this->limits);
        unset($this->commissions);
        unset($this->wallets);
        unset($this->limit_counts);
        unset($this->methods_list);
        unset($this->allowed_methods);
        unset($this->comment);

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
    public function getAddressNumber()
    {
        return $this->address_number;
    }

    /**
     * @param mixed $addres_number
     */
    public function setAddressNumber($address_number)
    {
        $this->address_number = $address_number;
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
    public function getTown()
    {
        return $this->town;
    }

    /**
     * @param mixed $town
     */
    public function setTown($town)
    {
        $this->town = $town;
    }

    /**
     * @param mixed $group_creator_data
     */
    public function setGroupCreatorData($group_creator_data)
    {
        $this->group_creator_data = $group_creator_data;
    }

}