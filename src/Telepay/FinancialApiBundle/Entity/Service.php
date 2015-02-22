<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;


class Service {

    private $id;

    private $name;

    private $cannonical_name;

    private $url;

    private $base64_image;

    private $role;

    public function __construct($id, $name, $cname, $base64Image, $role){
        $this->id=$id;
        $this->name=$name;
        $this->role=$role;
        $this->cannonical_name=$cname;
        $this->base64_image=$base64Image;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
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
    public function getCannonicalName()
    {
        return $this->cannonical_name;
    }

    /**
     * @return mixed
     */
    public function getBase64Image()
    {
        return $this->base64_image;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }


}