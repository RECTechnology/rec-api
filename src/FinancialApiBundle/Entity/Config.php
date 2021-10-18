<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Tier
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class Config extends AppObject {

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $min_version_android = 201;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $min_version_ios = 201;

    /**
     * @return int
     */
    public function getMinVersionAndroid(): int
    {
        return $this->min_version_android;
    }

    /**
     * @param int $min_version_android
     */
    public function setMinVersionAndroid(int $min_version_android): void
    {
        $this->min_version_android = $min_version_android;
    }

    /**
     * @return int
     */
    public function getMinVersionIos(): int
    {
        return $this->min_version_ios;
    }

    /**
     * @param int $min_version_ios
     */
    public function setMinVersionIos(int $min_version_ios): void
    {
        $this->min_version_ios = $min_version_ios;
    }



}