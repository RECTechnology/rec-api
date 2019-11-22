<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Exception\AppLogicException;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Document
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class Document extends AppObject implements Uploadable, Stateful {

    const DOCTYPE_LW_ID = 0;
    const DOCTYPE_LW_PROOF_OF_ADDRESS = 1;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "created"={"to"={"uploaded"}},
     *     "uploaded"={"to"={"submitted"}},
     *     "submitted"={"to"={"approved", "declined"}},
     *     "declined"={"to"={"archived"}},
     *     "approved"={"final"=true},
     *     "archived"={"final"=true},
     * }, initial="created")
     * @Serializer\Groups({"manager"})
     */
    private $status;

    /**
     * @var string $name
     * @ORM\Column(type="string")
     * @Serializer\Groups({"manager"})
     */
    private $name;

    /**
     * @var string $type
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"manager"})
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group", inversedBy="documents")
     * @Serializer\Groups({"manager"})
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\DocumentKind", inversedBy="documents")
     * @Serializer\Groups({"manager"})
     */
    private $kind;


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
        if($this->status != self::STATUS_CREATED || $this->status != null)
            throw new AppLogicException("Setting content is only available when status is 'created'");
        $this->content = $content;
        $this->status = self::STATUS_UPLOADED;
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
}