<?php
/**
 *  Author: Lluis Santos
 *  Date: 24 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\FinancialApiBundle\Annotations as REC;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Mailing
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Mailing extends AppObject implements Translatable {

    use TranslatableTrait;

    /**
     * @REC\TranslatedProperty
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $subject;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $subject_es;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $subject_ca;

    /**
     * @REC\TranslatedProperty
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $content;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $content_es;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     * @Serializer\Groups({"admin"})
     */
    private $content_ca;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"admin"})
     */
    private $processed;

    /**
     * @REC\TranslatedProperty
     * @ORM\Column(type="json_array", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $attachments;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @Serializer\Groups({"admin"})
     */
    private $attachments_es;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @Serializer\Groups({"admin"})
     */
    private $attachments_ca;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\MailingDelivery", mappedBy="mailing")
     * @Serializer\Groups({"admin"})
     */
    private $deliveries;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $scheduled_at;

    /**
     * Activity constructor.
     */
    public function __construct() {
        $this->processed = false;
        $this->deliveries = new ArrayCollection();
        $this->attachments = [];
    }

    /**
     * @return mixed
     */
    public function getDeliveries()
    {
        return $this->deliveries;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param mixed $attachments
     */
    public function setAttachments($attachments): void
    {
        $this->attachments = $attachments;
    }

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
    public function setScheduledAt($scheduled_at): void
    {
        $this->scheduled_at = $scheduled_at;
    }

    /**
     * @return mixed
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * @param mixed $processed
     */
    public function setProcessed($processed): void
    {
        $this->processed = $processed;
    }

}