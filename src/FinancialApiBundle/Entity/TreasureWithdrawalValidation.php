<?php

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Annotations\StatusProperty;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class TreasureWithdrawalValidation
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class TreasureWithdrawalValidation extends AppObject implements Stateful {

    const DEFAULT_TOKEN_BYTES = 40;

    use StatefulTrait;

    /**
     * @ORM\Column(type="string")
     * @StatusProperty(
     *     initial="created",
     *     choices={
     *          "created"={"to"={"sent"}},
     *          "sent"={"to"={"approved"}},
     *          "approved"={"final"=true}
     *      }
     * )
     * @Serializer\Groups({"admin"})
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="TreasureWithdrawal", inversedBy="validations")
     * @Serializer\Groups({"admin"})
     */
    private $withdrawal;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\TreasureWithdrawalAuthorizedEmail", inversedBy="withdrawals")
     * @Serializer\Groups({"admin"})
     */
    private $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(min="40")
     * @Serializer\Groups({"admin"})
     */
    private $token;

    /**
     * TreasureWithdrawalValidation constructor.
     */
    public function __construct()
    {
        $this->token = sha1(random_bytes(self::DEFAULT_TOKEN_BYTES));
    }

    /**
     * @return mixed
     */
    public function getWithdrawal()
    {
        return $this->withdrawal;
    }

    /**
     * @param mixed $withdrawal
     */
    public function setWithdrawal($withdrawal)
    {
        $this->withdrawal = $withdrawal;
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
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

}