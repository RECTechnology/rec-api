<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="KYC")
 * @ExclusionPolicy("all")
 */
class KYC implements Uploadable {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     * @Groups({"user"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $name = "";

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $lastName = "";

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $full_name_validated = false;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $email = "";

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $email_validated = false;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $phone = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"user"})
     */
    private $validation_phone_code;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $phone_validated = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $dateBirth;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $dateBirth_validated = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $document_front;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $document_front_status='pending';

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $document_rear;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $document_rear_status='pending';

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $document_validated = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $first_transaction;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $card_info;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $other_info;

    /**
     * @ORM\OneToOne(targetEntity="App\FinancialApiBundle\Entity\User")
     * @Expose
     * @Groups({"user"})
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $country = "";

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $country_validated = false;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $neighborhood = "";

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $street_type = "";

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $street_number = "";

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $street_name = "";

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $zip;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $address_validated = false;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"user"})
     */
    private $proof_of_residence = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice(
     *     choices={"M", "F", "NB"},
     *     message="Invalid value for gender, valid options: M, F, NB"
     * )
     * @Expose
     * @Groups({"user"})
     */
    private $gender;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"user"})
     */
    private $nationality = "ESP";

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $tier1_status;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $tier2_status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $tier1_status_request;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Expose
     * @Groups({"user"})
     */
    private $tier2_status_request;

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
    public function getFirstTransaction()
    {
        return $this->first_transaction;
    }

    /**
     * @param mixed $first_transaction
     */
    public function setFirstTransaction($first_transaction)
    {
        $this->first_transaction = $first_transaction;
    }

    /**
     * @return mixed
     */
    public function getCardInfo()
    {
        return $this->card_info;
    }

    /**
     * @param mixed $card_info
     */
    public function setCardInfo($card_info)
    {
        $this->card_info = $card_info;
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
    public function setUser(User $user)
    {
        $this->user = $user;
        if($user->getKycValidations() != $this)
            $user->setKycValidations($this);
    }

    /**
     * @return mixed
     */
    public function getOtherInfo()
    {
        return $this->other_info;
    }

    /**
     * @param mixed $other_info
     */
    public function setOtherInfo($other_info)
    {
        $this->other_info = $other_info;
    }

    /**
     * @return mixed
     */
    public function getPhoneValidated()
    {
        return $this->phone_validated;
    }

    /**
     * @param mixed $phone_validated
     */
    public function setPhoneValidated($phone_validated)
    {
        $this->phone_validated = $phone_validated;
    }

    /**
     * @return mixed
     */
    public function getEmailValidated()
    {
        return $this->email_validated;
    }

    /**
     * @param mixed $email_validated
     */
    public function setEmailValidated($email_validated)
    {
        $this->email_validated = $email_validated;
    }

    /**
     * @return mixed
     */
    public function getDocumentValidated()
    {
        return $this->document_validated;
    }

    /**
     * @param mixed $document_validated
     */
    public function setDocumentValidated($document_validated)
    {
        $this->document_validated = $document_validated;
    }

    /**
     * @return mixed
     */
    public function getDocumentFront()
    {
        return $this->document_front;
    }

    /**
     * @param mixed $document_front
     */
    public function setDocumentFront($document_front)
    {
        $this->document_front = $document_front;
    }

    /**
     * @return mixed
     */
    public function getDocumentFrontStatus()
    {
        return $this->document_front_status;
    }

    /**
     * @param mixed $document_front_status
     */
    public function setDocumentFrontStatus($document_front_status)
    {
        $this->document_front_status = $document_front_status;
    }

    /**
     * @return mixed
     */
    public function getValidationPhoneCode()
    {
        return $this->validation_phone_code;
    }

    /**
     * @param mixed $validation_phone_code
     */
    public function setValidationPhoneCode($validation_phone_code)
    {
        $this->validation_phone_code = $validation_phone_code;
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
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getDateBirth()
    {
        return $this->dateBirth;
    }

    /**
     * @param mixed $dateBirth
     */
    public function setDateBirth($dateBirth)
    {
        $this->dateBirth = $dateBirth;
    }

    /**
     * @return mixed
     */
    public function getFullNameValidated()
    {
        return $this->full_name_validated;
    }

    /**
     * @param mixed $full_name_validated
     */
    public function setFullNameValidated($full_name_validated)
    {
        $this->full_name_validated = $full_name_validated;
    }

    /**
     * @return mixed
     */
    public function getDateBirthValidated()
    {
        return $this->dateBirth_validated;
    }

    /**
     * @param mixed $dateBirth_validated
     */
    public function setDateBirthValidated($dateBirth_validated)
    {
        $this->dateBirth_validated = $dateBirth_validated;
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
    public function getCountryValidated()
    {
        return $this->country_validated;
    }

    /**
     * @param mixed $country_validated
     */
    public function setCountryValidated($country_validated)
    {
        $this->country_validated = $country_validated;
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
    public function getStreetName()
    {
        return $this->street_name;
    }

    /**
     * @param mixed $street_name
     */
    public function setStreetName($street_name)
    {
        $this->street_name = $street_name;
    }

    /**
     * @return mixed
     */
    public function getStreetNumber()
    {
        return $this->street_number;
    }

    /**
     * @param mixed $street_number
     */
    public function setStreetNumber($street_number)
    {
        $this->street_number = $street_number;
    }


    /**
     * @return mixed
     */
    public function getAddressValidated()
    {
        return $this->address_validated;
    }

    /**
     * @param mixed $address_validated
     */
    public function setAddressValidated($address_validated)
    {
        $this->address_validated = $address_validated;
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
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * @param mixed $nationality
     */
    public function setNationality($nationality){
        if(strlen($nationality) != 3){
            throw new AppLogicException("Invalid ISO-3 country code: '$nationality' is not ISO-3 compliant");
        }
        $this->nationality = $nationality;
    }

    /**
     * @return mixed
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param mixed $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return mixed
     */
    public function getTier1Status()
    {
        return $this->tier1_status;
    }

    /**
     * @param mixed $tier1_status
     */
    public function setTier1Status($tier1_status)
    {
        $this->tier1_status = $tier1_status;
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

    /**
     * @return mixed
     */
    public function getTier2StatusRequest()
    {
        return $this->tier2_status_request;
    }

    /**
     * @param mixed $tier2_status_request
     */
    public function setTier2StatusRequest($tier2_status_request)
    {
        $this->tier2_status_request = $tier2_status_request;
    }

    /**
     * @return mixed
     */
    public function getTier1StatusRequest()
    {
        return $this->tier1_status_request;
    }

    /**
     * @param mixed $tier1_status_request
     */
    public function setTier1StatusRequest($tier1_status_request)
    {
        $this->tier1_status_request = $tier1_status_request;
    }

    /**
     * @return mixed
     */
    public function getDocumentRear()
    {
        return $this->document_rear;
    }

    /**
     * @param mixed $document_rear
     */
    public function setDocumentRear($document_rear)
    {
        $this->document_rear = $document_rear;
    }

    /**
     * @return mixed
     */
    public function getDocumentRearStatus()
    {
        return $this->document_rear_status;
    }

    /**
     * @param mixed $document_rear_status
     */
    public function setDocumentRearStatus($document_rear_status)
    {
        $this->document_rear_status = $document_rear_status;
    }

    function getUploadableFields()
    {
        return [
            'document_front' => UploadManager::$FILTER_DOCUMENTS,
            'document_rear' => UploadManager::$FILTER_DOCUMENTS,
        ];
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
    public function setZip($zip): void
    {
        $this->zip = $zip;
    }
}