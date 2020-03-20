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
class LemonDocument extends Document implements LemonObject {
    const LW_STATUS_APPROVED = [2];
    const LW_STATUS_DECLINED = [3, 4, 5, 6, 7];

    use LemonObjectTrait;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "created"={"to"={"submitted", "auto_fetched"}},
     *     "submitted"={"to"={"approved", "declined"}},
     *     "declined"={"to"={"archived"}},
     *     "auto_fetched"={"to"={"approved"}},
     *     "approved"={"final"=true},
     *     "archived"={"final"=true},
     * }, initial_statuses={"submitted", "auto_fetched"})
     * @Serializer\Groups({"manager"})
     */
    protected $status;
}
