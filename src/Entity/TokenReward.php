<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints AS Assert;

/**
 * Class TokenReward
 * @package App\Entity
 * @ORM\Entity
 */
class TokenReward extends AppObject
{
    public const STATUS_MINTED = "minted";
    public const STATUS_CREATED = "created";

    public const STATUSES = [
        self::STATUS_CREATED,
        self::STATUS_MINTED
    ];

    /**
     * @ORM\Column(type="string", length=60)
     * @Serializer\Groups({"admin", "user"})
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true )
     * @Serializer\Groups({"admin", "user"})
     */
    private $description;

    /**
     * @ORM\Column(type="string")
     * @Assert\Url(message="This is not a valid url")
     * @Serializer\Groups({"admin", "user"})
     */
    private $image;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"admin", "user"})
     */
    private $token_id;

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice(choices=TokenReward::STATUSES)
     * @Serializer\Groups({"admin", "user"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Url(message="This is not a valid url")
     * @Serializer\Groups({"admin", "user"})
     */
    private $author_url;

    /**
     * One Token reward has One Challenge or null.
     * @ORM\OneToOne(targetEntity="App\Entity\Challenge")
     * @Serializer\Groups({"admin", "user"})
     */
    private $challenge;

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
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image): void
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getTokenId()
    {
        return $this->token_id;
    }

    /**
     * @param mixed $token_id
     */
    public function setTokenId($token_id): void
    {
        $this->token_id = $token_id;
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

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getAuthorUrl()
    {
        return $this->author_url;
    }

    /**
     * @param mixed $author_url
     */
    public function setAuthorUrl($author_url): void
    {
        $this->author_url = $author_url;
    }

    /**
     * @return mixed
     */
    public function getChallenge()
    {
        return $this->challenge;
    }

    /**
     * @param mixed $challenge
     */
    public function setChallenge($challenge): void
    {
        $this->challenge = $challenge;
    }


}