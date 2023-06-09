<?php

namespace App\Validator\Constraint;

use App\Controller\Google2FA;
use App\Entity\PaymentOrder;
use App\Entity\PaymentOrderUsedNonce;
use App\Entity\Pos;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class ValidPaymentOrderValidator
 * @package App\Validator\Constraint
 */
class ValidPaymentOrderValidator extends ConstraintValidator {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestStack $requestStack */
    private $requestStack;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $auth;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * ValidSignatureValidator constructor.
     * @param EntityManagerInterface $em
     * @param RequestStack $requestStack
     * @param AuthorizationCheckerInterface $auth
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $em,
        RequestStack $requestStack,
        AuthorizationCheckerInterface $auth,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->auth = $auth;
        $this->tokenStorage = $tokenStorage;
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
        if ($this->tokenStorage->getToken() && $this->auth->isGranted("ROLE_ADMIN")) {
            if(!$this->otpIsValid())
                $this->context->buildViolation("OTP code is not valid")
                    ->atPath('otp')
                    ->addViolation();
            return;
        }
        else {

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
                return;
            }
            $dataToSign = $this->requestStack->getCurrentRequest()->request->all();
            if(!key_exists("signature_version", $dataToSign)){
                $this->context->buildViolation("signature_version not specified")
                    ->atPath('signature_version')
                    ->addViolation();
                return;
            }

            if(!key_exists("nonce", $dataToSign)){
                $this->context->buildViolation("nonce not specified")
                    ->atPath('nonce')
                    ->addViolation();
                return;
            }

            if(!$this->isValidNonce($dataToSign['nonce'], $pos)){
                $this->context->buildViolation("nonce not valid")
                    ->atPath('nonce')
                    ->addViolation();
                return;
            }
            unset($dataToSign['signature']);

            ksort($dataToSign);
            $signaturePack = json_encode($dataToSign, JSON_UNESCAPED_SLASHES);

            $calculated_signature = hash_hmac('sha256', $signaturePack, base64_decode($pos->getAccessSecret()));

            if($order->getSignature() !== $calculated_signature) {
                $this->context->buildViolation("signature is not valid")
                    ->atPath('signature')
                    ->addViolation();
                return;
            }

            //save nonce
            $usedNonce = new PaymentOrderUsedNonce();
            $usedNonce->setNonce($dataToSign['nonce']);
            $usedNonce->setPos($pos);
            $this->em->persist($usedNonce);
            $this->em->flush();

        }
    }

    private function otpIsValid() {
        /* check otp matches with current user */
        $currentRequest = $this->requestStack->getCurrentRequest();
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $otp = Google2FA::oath_totp($user->getTwoFactorCode());
        return $otp == $currentRequest->request->get('otp');
    }

    private function isValidNonce($nonce,Pos $pos)
    {

        $nowTimestamp = round(microtime(true) * 1000, 0);
        return ((int) $nonce === $nonce)
            && ($nonce <= PHP_INT_MAX)
            && ($nonce >= ~PHP_INT_MAX)
            && ($nonce <= $nowTimestamp)
            && ($nonce >= $nowTimestamp - 300000)
            && ($this->isUniqueNonce($nonce, $pos));
    }

    private function isUniqueNonce($nonce, Pos $pos){
        $usedNonce = $this->em->getRepository(PaymentOrderUsedNonce::class)->findOneBy(array(
            "nonce" => $nonce,
            "pos" => $pos
        ));

        if($usedNonce) return false;

        return true;
    }
}