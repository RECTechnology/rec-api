<?php

namespace App\FinancialApiBundle\Validator\Constraint;

use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class ValidPaymentOrderValidator
 * @package App\FinancialApiBundle\Validator\Constraint
 */
class ValidPaymentOrderValidator extends ConstraintValidator {

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * ValidSignatureValidator constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Checks if the signature is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @api
     */
    public function validate($value, Constraint $constraint)
    {
        assert($value instanceof PaymentOrder);
        $order = $value;

        $repo = $this->em->getRepository(Pos::class);

        /** @var Pos $pos */
        $pos = $repo->findOneBy(['access_key' => $order->getAccessKey()]);
        if(!$pos){
            $this->context->buildViolation("Property access_key is not valid")
                ->atPath('access_key')
                ->addViolation();
            return;
        }

        if(!$pos->getActive()){
            $this->context->buildViolation("POS is not active")
                ->atPath('access_key')
                ->addViolation();
        }

        $dataToSign = [
            'access_key' => $order->getAccessKey(),
            'amount' => $order->getAmount(),
            'concept' => $order->getConcept(),
            'ko_url' => $order->getKoUrl(),
            'ok_url' => $order->getOkUrl(),
            'reference' => $order->getReference()
        ];

        ksort($dataToSign);
        $signaturePack = json_encode($dataToSign, JSON_UNESCAPED_SLASHES);

        $calculated_signature = hash_hmac('sha256', $signaturePack, base64_decode($pos->getAccessSecret()));

        if($order->getSignature() !== $calculated_signature) {
            $this->context->buildViolation("signature is not valid")
                ->atPath('signature')
                ->addViolation();
        }

    }
}