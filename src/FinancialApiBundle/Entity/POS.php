<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity
 * @ORM\Table(name="POS")
 * @ExclusionPolicy("all")
 */
class POS {

    public function __construct()
    {
        $this->linking_code = $this->generateCode(6);
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;


    /**
     * @ORM\Column(type="string", unique=true)
     * @Expose
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     */
    private $group;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $cname;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $currency;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $type;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $pos_id;

    /**
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $expires_in;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $active;

    /**
     * @Expose
     */
    private $url;

    /**
     * @ORM\Column (type="string")
     * @Expose
     */
    private $linking_code;

    /**
     * @ORM\Column (type="boolean")
     * @Expose
     */
    private $linked = false;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCname()
    {
        return $this->cname;
    }

    /**
     * @param mixed $cname
     */
    public function setCname($cname)
    {
        $this->cname = $cname;
    }

    /**
     * @return mixed
     */
    public function getTpvView()
    {
        $this->url = 'https://pos.chip-chap.com/'.$this->getPosId();
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getPosId()
    {
        return $this->pos_id;
    }

    /**
     * @param mixed $pos_id
     */
    public function setPosId($pos_id)
    {
        $this->pos_id = $pos_id;
    }

    /**
     * @return mixed
     */
    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    /**
     * @param mixed $expires_in
     */
    public function setExpiresIn($expires_in)
    {
        $this->expires_in = $expires_in;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;
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
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return mixed
     */
    public function getLinkingCode()
    {
        return $this->linking_code;
    }

    public function generateCode($longitud) {
        $key = '';
        $pattern = '1234567890';
        $max = strlen($pattern)-1;
        for($i=0;$i < $longitud;$i++) $key .= $pattern{mt_rand(0,$max)};
        return $key;
    }

    /**
     * @param mixed $linking_code
     */
    public function setLinkingCode($linking_code)
    {
        $this->linking_code = $linking_code;
    }

    /**
     * @return mixed
     */
    public function getLinked()
    {
        return $this->linked;
    }

    /**
     * @param mixed $linked
     */
    public function setLinked($linked)
    {
        $this->linked = $linked;
    }

}