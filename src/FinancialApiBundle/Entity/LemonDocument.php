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
    const LW_STATUS_APPROVED = [2];
    const LW_STATUS_DECLINED = [3, 4, 5, 6, 7];

    /**
     * @var string $lemon_reference
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $lemon_reference;

    /**
     * @var integer $lemon_status
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"user"})
     */
    private $lemon_status;

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

    /**
     * @param $lemon_status
     */
    public function setLemonStatus($lemon_status)
    {
        $this->lemon_status = $lemon_status;
    }

    /**
     * @return integer
     */
    public function getLemonStatus(): int
    {
        return $this->lemon_status;
    }
}
