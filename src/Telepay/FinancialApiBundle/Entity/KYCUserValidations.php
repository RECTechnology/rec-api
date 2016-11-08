<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Entity
 * @ORM\Table(name="kyc_user_validations")
 * @ExclusionPolicy("none")
 */
class KYCUserValidations
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $email = false;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $phone = false;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $full_name = false;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $birth_date = false;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $country = false;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $address = false;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $proof_of_residence = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $tier_1_file;

    /**
     * @ORM\Column(type="string", nullable = true)
     * @Expose
     */
    private $tier_2_file;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
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
    public function getFullName()
    {
        return $this->full_name;
    }

    /**
     * @param mixed $full_name
     */
    public function setFullName($full_name)
    {
        $this->full_name = $full_name;
    }

    /**
     * @return mixed
     */
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * @param mixed $birth_date
     */
    public function setBirthDate($birth_date)
    {
        $this->birth_date = $birth_date;
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
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getProofOfResidence()
    {
        return $this->proof_of_residence;
    }

    /**
     * @param mixed $proof_of_residence
     */
    public function setProofOfResidence($proof_of_residence)
    {
        $this->proof_of_residence = $proof_of_residence;
    }

    /**
     * @return mixed
     */
    public function getTier1File()
    {
        return $this->tier_1_file;
    }

    /**
     * @param mixed $tier_1_file
     */
    public function setTier1File($tier_1_file)
    {
        $this->tier_1_file = $tier_1_file;
    }

    /**
     * @return mixed
     */
    public function getTier2File()
    {
        return $this->tier_2_file;
    }

    /**
     * @param mixed $tier_2_file
     */
    public function setTier2File($tier_2_file)
    {
        $this->tier_2_file = $tier_2_file;
    }


}