<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Exception\AppLogicException;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Iban
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class Iban extends AppObject implements Stateful, LemonObject {

    use LemonObjectTrait;
    const LW_STATUS_APPROVED = [5];
    const LW_STATUS_DECLINED = [1, 2, 3, 6, 7, 8, 9];

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "created"={"to"={"approved", "declined"}},
     *     "declined"={"to"={"archived"}},
     *     "approved"={"final"=true},
     *     "archived"={"final"=true},
     * }, initial="created")
     * @Serializer\Groups({"manager"})
     */
    protected $status;

    /**
     * @var string $name
     * @ORM\Column(type="string")
     * @Serializer\Groups({"manager"})
     */
    protected $name;

    /**
     * @var string $holder
     * @ORM\Column(type="string")
     * @Serializer\Groups({"manager"})
     */
    protected $holder;

    /**
     * @var string $bic
     * @ORM\Column(type="string")
     * @Serializer\Groups({"manager"})
     */
    protected $bic;

    /**
     * @var string $bank_name
     * @ORM\Column(type="string")
     * @Serializer\Groups({"manager"})
     */
    protected $bank_name;

    /**
     * @var string $bank_address
     * @ORM\Column(type="string")
     * @Serializer\Groups({"manager"})
     */
    protected $bank_address;

    /**
     * @var string $number
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Iban()
     * @Serializer\Groups({"manager"})
     */
    protected $number;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="documents")
     * @Serializer\Groups({"manager"})
     */
    protected $account;

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param mixed $account
     */
    public function setAccount($account): void
    {
        $this->account = $account;
    }

    /**
     * @return string
     */
    public function getHolder(): string
    {
        return $this->holder;
    }

    /**
     * @param string $holder
     */
    public function setHolder(string $holder): void
    {
        $this->holder = $holder;
    }

    /**
     * @return string
     */
    public function getBic(): string
    {
        return $this->bic;
    }

    /**
     * @param string $bic
     */
    public function setBic(string $bic): void
    {
        $this->bic = $bic;
    }

    /**
     * @return string
     */
    public function getBankName(): string
    {
        return $this->bank_name;
    }

    /**
     * @param string $bank_name
     */
    public function setBankName(string $bank_name): void
    {
        $this->bank_name = $bank_name;
    }

    /**
     * @return string
     */
    public function getBankAddress(): string
    {
        return $this->bank_address;
    }

    /**
     * @param string $bank_address
     */
    public function setBankAddress(string $bank_address): void
    {
        $this->bank_address = $bank_address;
    }
}
