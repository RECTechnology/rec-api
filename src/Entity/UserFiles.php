<?php

namespace App\Entity;

use App\DependencyInjection\Commons\UploadManager;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Config\Definition\Exception\Exception;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class UserFiles implements Uploadable {

    protected $list_tags = array(
        "banco" => 2,
        "autonomo" => 6,
        "cif" => 6,
        "modelo03x" => 7,
        "modelo200_o_titularidad" => 7,
        "estatutos" => 12,
        "pasaporte" => 14,
        "otroDNI_front" => 15,
        "otroDNI2_front" => 17,
        "otroDNI_rear" => 16,
        "otroDNI2_rear" => 18,
        "poderes" => 19
    );

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

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
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
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $deleted = false;

    /**
     * Check the tag inserted
     *
     * @return bool
     */
    public function checkTag($tag){
        return array_key_exists($tag, $this->list_tags);
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
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param mixed $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
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

    /**
     * @return mixed
     */
    public function getType(){
        return $this->list_tags[$this->tag];
    }

    function getUploadableFields()
    {
        return ['url' => UploadManager::$FILTER_DOCUMENTS];
    }

}