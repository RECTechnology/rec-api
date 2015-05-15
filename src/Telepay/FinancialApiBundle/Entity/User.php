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
     * @ORM\ManyToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     * @ORM\JoinTable(name="fos_user_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

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
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\LimitCount", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $limit_count;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\UserWallet", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $wallets;

    /**
     * @ORM\OneToMany(targetEntity="Telepay\FinancialApiBundle\Entity\Device", mappedBy="user", cascade={"remove"})
     * @Expose
     */
    private $device;

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
     * @ORM\Column(type="string")
     * @Expose
     */
    private $default_currency;

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
     * @Expose
     */
    private $allowed_services = array();

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
     * @param Service $service
     */
    public function addAllowedService($service)
    {
        $this->addRole($service->getRole());
    }

    /**
     * @param array $allowed_services
     */
    public function setAllowedServices($allowed_services)
    {
        $this->allowed_services = $allowed_services;
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
    public function getLimitCount()
    {
        return $this->limit_count;
    }

    /**
     * @param mixed $limit_count
     */
    public function setLimitCount($limit_count)
    {
        $this->limit_count = $limit_count;
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
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param mixed $device
     */
    public function setDevice($device)
    {
        $this->device = $device;
    }

}