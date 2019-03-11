<?php

namespace Telepay\FinancialApiBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Config\Definition\Exception\Exception;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class UserFiles{

    public function __construct(){
        $this->created = new \DateTime();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    protected $list_tags = array(
        "banco", "autonomo", "cif", "censo", "titularidad","pasaporte","modelo03x","modelo200","otroDNI_front","otroDNI_rear"
    );

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $url;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $extension;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $tag;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $description = "";

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $status;

    /**
     * Check the tag inserted
     *
     * @return bool
     */
    public function checkTag($tag){
        return in_array($tag, $this->list_tags);
    }

    /**
     * Returns the user unique id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return mixed
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param mixed $extension
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
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
    public function setDescription($description)
    {
        $this->description = $description;
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
    public function getTag(){
        return $this->tag;
    }

    /**
     * @param mixed $tag
     */
    public function setTag($tag){
        if($this->checkTag($tag)) {
            $this->tag = $tag;
        }
        else{
            throw new Exception('Tag is not valid');
        }
    }
}