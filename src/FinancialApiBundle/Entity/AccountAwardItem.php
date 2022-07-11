<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class AccountAwardItem
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class AccountAwardItem extends AppObject
{
    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    private $score;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\AccountAward")
     * @Serializer\Groups({"user"})
     * @MaxDepth(1)
     */
    private $account_award;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $topic_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $post_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $user_id;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    private $action;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"public"})
     */
    private $category;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
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
    public function getAccountAward()
    {
        return $this->account_award;
    }

    /**
     * @param mixed $account_award
     */
    public function setAccountAward($account_award): void
    {
        $this->account_award = $account_award;
    }

    /**
     * @return mixed
     */
    public function getTopicId()
    {
        return $this->topic_id;
    }

    /**
     * @param mixed $topic_id
     */
    public function setTopicId($topic_id): void
    {
        $this->topic_id = $topic_id;
    }

    /**
     * @return mixed
     */
    public function getPostId()
    {
        return $this->post_id;
    }

    /**
     * @param mixed $post_id
     */
    public function setPostId($post_id): void
    {
        $this->post_id = $post_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id): void
    {
        $this->user_id = $user_id;
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