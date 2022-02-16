<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class LemonDocumentKind
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class LemonDocumentKind extends DocumentKind {

    public const LW_DOCTYPES = [0,1,2,3,4,5,7,11,12,13,14,15,16,17,18,19,21];

    public const DOCTYPE_LW_ID = 0;
    public const DOCTYPE_LW_PROOF_OF_ADDRESS = 1;
    public const DOCTYPE_LW_PROOF_OF_IBAN = 2;
    public const DOCTYPE_LW_EU_PASSPORT = 3;
    public const DOCTYPE_LW_NON_EU_PASSPORT = 4;
    public const DOCTYPE_LW_RESIDENCE_PERMIT = 5;
    public const DOCTYPE_LW_PROOF_OF_COMPANY_REGISTRATION = 7;
    public const DOCTYPE_LW_DRIVER_LICENSE = 11;
    public const DOCTYPE_LW_STATUS = 12;
    public const DOCTYPE_LW_SELFIE = 13;
    public const DOCTYPE_LW_COMMERCIAL_REGISTER = 14;
    public const DOCTYPE_LW_SECOND_ID = 15;
    public const DOCTYPE_LW_CENSUS_STATUS_CERTIFICATE = 16;
    public const DOCTYPE_LW_DECLARATION_OF_ACTIVITY = 17;
    public const DOCTYPE_LW_GENERAL_ASSEMBLY_ACT  = 18;
    public const DOCTYPE_LW_OFFICIAL_CORPORATE_JOURNAL = 19;
    public const DOCTYPE_LW_OTHERS = 20;
    public const DOCTYPE_LW_SDD_MANDAT = 21;

    /**
     * @var int $lemon_doctype
     * @ORM\Column(type="integer")
     * @Assert\Choice(
     *     choices=LemonDocumentKind::LW_DOCTYPES,
     *     message="doctype must be specified in http://documentation.lemonway.fr/api-en/directkit/manage-wallets/uploadfile-document-upload-for-kyc"
     * )
     * @Serializer\Groups({"user"})
     */
    private $lemon_doctype;

    /**
     * @return int
     */
    public function getLemonDoctype(): int
    {
        return $this->lemon_doctype;
    }

    /**
     * @param int $lemon_doctype
     */
    public function setLemonDoctype(int $lemon_doctype): void
    {
        $this->lemon_doctype = $lemon_doctype;
    }


}