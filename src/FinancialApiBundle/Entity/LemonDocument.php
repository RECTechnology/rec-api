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

    const LW_STATUSES = [
        "on_hold",
        "unverified",
        "approved",
        "declined",
        "unreadable",
        "expired",
        "wrong_type",
        "wrong_name",
        "duplicated"
    ];

    use LemonObjectTrait;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     * @StatusProperty(choices={
     *     "submitted"={"to"={"approved", "declined", "on_hold", "unverified", "unreadable", "expired", "wrong_type", "wrong_name","duplicated"}},
     *     "unverified"={"to"={"approved", "declined", "on_hold", "unreadable", "expired", "wrong_type", "wrong_name"}},
     *     "on_hold"={"to"={"approved", "declined", "unreadable", "expired", "wrong_type", "wrong_name"}},
     *     "declined"={"to"={"archived"}},
     *     "unreadable"={"to"={"archived"}},
     *     "expired"={"to"={"archived"}},
     *     "wrong_type"={"to"={"archived"}},
     *     "wrong_name"={"to"={"archived"}},
     *     "auto_fetched"={"to"={"approved"}},
     *     "approved"={"to"={"expired"}},
     *     "archived"={"final"=true},
     *     "duplicated"={"final"=true},
     * })
     * @Serializer\Groups({"manager"})
     */
    protected $status;
}
