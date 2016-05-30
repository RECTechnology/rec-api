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
     * @ORM\ManyToMany(targetEntity="Telepay\FinancialApiBundle\Entity\User", mappedBy="groups")
     * @Exclude
     */
    protected $users;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    private $creator;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $group_creator;

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
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Balance", mappedBy="group", cascade={"remove"})
     */
    private $balance;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Client", mappedBy="group", cascade={"remove"})
     * @Expose
     */
    private $clients;

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


}