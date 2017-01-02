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
 * @ORM\Table(name="kyc_company_validations")
 * @ExclusionPolicy("none")
 */
class KYCCompanyValidations
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $company;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $email;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $phone;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $cif;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $zip;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $city;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $country;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $address;

    /**
     * @ORM\Column(type="boolean" ,nullable=true)
     * @Expose
     */
    private $town;

    /**
     * @ORM\Column(type="string", nullable = true)
     * @Expose
     */
    private $tier_2_file;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $tier_2_file_description;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $tier2_status;

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
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
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
    public function getTown()
    {
        return $this->town;
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

    /**
     * @return mixed
     */
    public function getTier2FileDescription()
    {
        return $this->tier_2_file_description;
    }

    /**
     * @param mixed $tier_2_file_description
     */
    public function setTier2FileDescription($tier_2_file_description)
    {
        $this->tier_2_file_description = $tier_2_file_description;
    }

    /**
     * @return mixed
     */
    public function getTier2Status()
    {
        return $this->tier2_status;
    }

    /**
     * @param mixed $tier2_status
     */
    public function setTier2Status($tier2_status)
    {
        $this->tier2_status = $tier2_status;
    }


}