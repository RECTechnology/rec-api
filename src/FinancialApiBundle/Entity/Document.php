<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Exception\AppLogicException;
use Doctrine\ORM\Mapping as ORM;
use DoctrineExtensions\Query\Mysql\Date;
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

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "approved"={"final"=true}
     * }, initial="approved")
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
     * @var string $type
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Url()
     * @Serializer\Groups({"manager"})
     */
    protected $content;


    /**
     * @var \DateTime $type
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     * @Serializer\Groups({"manager"})
     */
    protected $valid_until;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="documents")
     * @Serializer\Groups({"manager"})
     */
    protected $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\DocumentKind", inversedBy="documents")
     * @Serializer\Groups({"manager"})
     * @Assert\NotNull()
     */
    protected $kind;


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
    public function getContent(): string
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
}
