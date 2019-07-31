<?php

namespace App\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class Category{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     * @Groups({"public"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"public"})
     */
    private $cat;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"public"})
     */
    private $eng;

    /**
     * @ORM\Column(type="string")
     * @Expose
     * @Groups({"public"})
     */
    private $esp;


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
    public function getCat()
    {
        return $this->cat;
    }

    /**
     * @param mixed $cat
     */
    public function setCat($cat)
    {
        $this->cat = $cat;
    }

    /**
     * @return mixed
     */
    public function getEsp()
    {
        return $this->esp;
    }

    /**
     * @param mixed $esp
     */
    public function setEsp($esp)
    {
        $this->esp = $esp;
    }

    /**
     * @return mixed
     */
    public function getEng()
    {
        return $this->eng;
    }

    /**
     * @param mixed $eng
     */
    public function setEng($eng)
    {
        $this->eng = $eng;
    }
}