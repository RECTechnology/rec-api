<?php

namespace Telepay\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JMS\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Telepay\FinancialApiBundle\Document\Transaction;


/**
 * @ORM\Entity
 */
class TreasureWithdrawalValidation extends AppObject {

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User", inversedBy="treasure_validations")
     * @Groups({"admin"})
     */
    private $validator;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\TreasureWithdrawalAttempt", inversedBy="validations")
     * @Groups({"admin"})
     */
    private $attempt;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"admin"})
     */
    private $accepted;


    /**
     * @return mixed
     */
    public function isAccepted()
    {
        return $this->accepted;
    }

    /**
     * @param mixed $accepted
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;
    }

    /**
     * @return mixed
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param mixed $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return mixed
     */
    public function getAttempt()
    {
        return $this->attempt;
    }

    /**
     * @param mixed $attempt
     */
    public function setAttempt($attempt)
    {
        $this->attempt = $attempt;
    }
}