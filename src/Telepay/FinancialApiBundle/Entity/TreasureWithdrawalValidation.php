<?php

namespace Telepay\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Telepay\FinancialApiBundle\Document\Transaction;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class TreasureWithdrawalValidation extends AppObject {

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     * @Expose
     */
    private $validator;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\TreasureWithdrawalAttempt", inversedBy="validations")
     * @Expose
     */
    private $attempt;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Expose
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