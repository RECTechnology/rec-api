<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Document
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
class Document extends AppObject implements Uploadable, Stateful {

    use StatefulTrait;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @Expose
     * @StatusProperty(choices={
     *     "rec_submitted"={"final"=false, "to"={"rec_declined", "rec_expired", "rec_approved"}},
     *     "rec_declined"={"final"=false, "to"={"rec_submitted"}},
     *     "rec_expired"={"final"=false, "to"={"rec_submitted"}},
     *     "rec_approved"={"final"=true},
     * }, initial_statuses={"rec_submitted"})
     * @Serializer\Groups({"user"})
     */
    protected $status;

    /**
     * @var string $name
     * @ORM\Column(type="string")
     * @Serializer\Groups({"manager"})
     */
    protected $name;

    /**
     * @var string $content
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Url()
     * @Serializer\Groups({"manager"})
     */
    protected $content;


    /**
     * @var \DateTime $valid_until
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     * @Serializer\Groups({"manager"})
     */
    protected $valid_until;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="documents")
     * @Serializer\Groups({"user"})
     * @Expose
     * @MaxDepth(1)
     */
    protected $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\DocumentKind", inversedBy="documents")
     * @Serializer\Groups({"user"})
     * @Assert\NotNull()
     * @Expose
     * @MaxDepth(1)
     */
    protected $kind;


    /**
     * @var mixed $user_id
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"user"})
     * @Expose
     * @MaxDepth(1)
     */
    protected $user_id;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\User", inversedBy="documents")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"user"})
     * @Expose
     * @MaxDepth(1)
     */
    protected $user;


    /**
     * @var string $status_text
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     * @Expose
     */
    protected $status_text;


    function getUploadableFields()
    {
        return ['content' => UploadManager::$FILTER_DOCUMENTS];
    }

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
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
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
     * @return mixed
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param mixed $kind
     */
    public function setKind($kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @return \DateTime
     */
    public function getValidUntil()
    {
        return $this->valid_until;
    }

    /**
     * @param \DateTime $valid_until
     */
    public function setValidUntil($valid_until): void
    {
        $this->valid_until = $valid_until;
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
    public function getStatusText()
    {
        return $this->status_text;
    }

    /**
     * @param string $status_text
     */
    public function setStatusText(string $status_text): void
    {
        $this->status_text = $status_text;
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
    public function setUser($user): void
    {
        $this->user = $user;
    }

}
