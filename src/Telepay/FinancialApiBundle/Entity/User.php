<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    public function __construct()
    {
        parent::__construct();
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
     */
    private $access_key;

    /**
     * @ORM\Column(type="string")
     */
    private $access_secret;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

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
     * @return array
     */
    public function getAllowedServices()
    {
        $services = array();
        $servicesRepo = new ServicesRepository();
        foreach($this->getRoles() as $role){
            try{
                $services []= $servicesRepo->findByRole($role);
            }
            catch(HttpException $e){ }
        }
        return $services;
    }

    /**
     * @param Service $service
     */
    public function addAllowedService(Service $service)
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

}