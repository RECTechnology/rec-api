<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Exception\AppLogicException;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class LemonDocument
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class LemonDocument extends Document {

    /**
     * @var string $lemon_reference
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $lemon_reference;

    /**
     * @return string
     */
    public function getLemonReference(): string
    {
        return $this->lemon_reference;
    }

    /**
     * @param string $lemon_reference
     */
    public function setLemonReference(string $lemon_reference): void
    {
        $this->lemon_reference = $lemon_reference;
    }
}
