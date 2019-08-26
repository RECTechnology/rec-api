<?php
/**
 *  Author: Lluis Santos
 *  Date: 24 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Mailing
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Mailing extends AppObject implements Translatable, Localizable {

    use LocalizableTrait;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string")
     * @Groups({"admin"})
     */
    private $subject;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"admin"})
     */
    private $content;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="json_array", nullable=true)
     * @Groups({"admin"})
     */
    private $attachments;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\MailingDelivery", inversedBy="mailing")
     * @Groups({"admin"})
     */
    private $deliveries;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $scheduled_at;

    /**
     * Activity constructor.
     */
    public function __construct() {
        $this->deliveries = new ArrayCollection();
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

}