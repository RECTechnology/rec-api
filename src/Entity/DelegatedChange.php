<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use LogicException;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraint as RECAssert;


/**
 * @ORM\Entity
 * @ORM\Table(name="delegated_changes")
 */
class DelegatedChange extends AppObject {

    const STATUS_CREATED = "created";
    const STATUS_PENDING_VALIDATION = "pending_validation";
    const STATUS_VALIDATING = "validating";
    const STATUS_INVALID = "invalid";
    const STATUS_DRAFT = "draft";
    const STATUS_SCHEDULED = "scheduled";
    const STATUS_IN_PROGRESS = "in_progress";
    const STATUS_FAILED = "failed";
    const STATUS_FINISHED = "finished";
    const STATUS_INCOMPLETE = "incomplete";

    const ALLOWED_STATUS_CHANGES = [
        ["old" => self::STATUS_DRAFT, "new" => self::STATUS_SCHEDULED],
        ["old" => self::STATUS_SCHEDULED, "new" => self::STATUS_DRAFT]
    ];

    public function __construct()
    {
        $this->data = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->status = DelegatedChange::STATUS_CREATED;
        $this->statistics = [
            "scheduled" => [
                "tx_to_execute" => 0,
                "rec_to_be_issued" => 0.
            ],
            "result" => [
                "success_tx" => 0,
                "failed_tx" => 0,
                "issued_rec" => 0,
                "warnings" => 0,
            ]
        ];
    }


    /**
     * @ORM\Column(type="string")
     * @Assert\Choice({"created", "pending_validation", "validating", "invalid", "draft", "scheduled", "in_progress", "failed", "finished", "incomplete"})
     * @Serializer\Groups({"admin"})
     */
    protected $status;

    /**
     * @Assert\IsTrue(message = "Delegated Change is not ready for schedule: maybe missing amounts? bank card data?")
     */
    public function isReadyForSchedule(){
        if($this->status === static::STATUS_SCHEDULED){
            /** @var DelegatedChangeData $dcd */
            foreach($this->getData() as $dcd){
                if($dcd->getAmount() === null) return false;
            }
            return true;
        }
        return true;
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DelegatedChangeData", mappedBy="delegated_change", cascade={"remove"})
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(2)
     */
    protected $data;

    /**
     * @return ArrayCollection
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TransactionBlockLog", mappedBy="block_txs", cascade={"remove"})
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(2)
     */
    protected $logs;

    /**
     * @return ArrayCollection
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @ORM\Column(type="json_array")
     * @Serializer\Groups({"admin"})
     */
    private $statistics;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $scheduled_at;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice({"delegated_change", "massive_transactions"})
     * @Serializer\Groups({"admin"})
     */
    private $type='delegated_change';


    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $url_csv;

    /**
     * @return mixed
     */
    public function getScheduledAt()
    {
        return $this->scheduled_at;
    }


    /**
     * @param mixed $scheduled_at
     */
    public function setScheduledAt($scheduled_at)
    {
        $this->scheduled_at = $scheduled_at;
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
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getStatistics()
    {
        return $this->statistics;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function setResult($key, $value)
    {
        if(!array_key_exists($key, $this->statistics['result']))
            throw new LogicException("Key '$key' not found in result");
        $this->statistics['result'][$key] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setRecToBeIssued($value)
    {
        $this->statistics['scheduled']['rec_to_be_issued'] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setTxToExecute($value)
    {
        $this->statistics['scheduled']['tx_to_execute'] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setWarnings($value)
    {
        $this->statistics['scheduled']['warnings'] = $value;
    }

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getUrlCsv()
    {
        return $this->url_csv;
    }

    /**
     * @param mixed $url_csv
     */
    public function setUrlCsv($url_csv): void
    {
        $this->url_csv = $url_csv;
    }



}