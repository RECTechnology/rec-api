<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class AwardScoreRule
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class AwardScoreRule extends AppObject
{
    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $score;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $action;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Award")
     * @Serializer\Groups({"admin"})
     * @MaxDepth(1)
     */
    private $award;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $category;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $scope;

    /**
     * @return mixed
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param mixed $score
     */
    public function setScore($score): void
    {
        $this->score = $score;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action): void
    {
        $this->action = $action;
    }

    /**
     * @return mixed
     */
    public function getAward()
    {
        return $this->award;
    }

    /**
     * @param mixed $award
     */
    public function setAward($award): void
    {
        $this->award = $award;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param mixed $scope
     */
    public function setScope($scope): void
    {
        $this->scope = $scope;
    }

}