<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class PaymentOrderSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class PaymentOrderSubscriber implements EventSubscriber {

    /** @var RequestStack $requestStack */
    private $requestStack;

    /** @var ContainerInterface $container */
    private $container;

    /** @var TokenStorageInterface $tokenStorage */
    private $tokenStorage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $auth;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param RequestStack $requestStack
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $auth
     * @param ContainerInterface $container
     */
    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $auth, ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->container = $container;
        $this->tokenStorage = $tokenStorage;
        $this->auth = $auth;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::prePersist,
            Events::postLoad,
            Events::postPersist,
            Events::preUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $order = $args->getEntity();
        if($order instanceof PaymentOrder){
            if ($args->hasChangedField("status")){
                if($args->getNewValue("status") == PaymentOrder::STATUS_REFUNDED){

                    $currentRequest = $this->requestStack->getCurrentRequest();
                    $refundAmount = $currentRequest->request->get("refund_amount", $order->getAmount());
                    if($refundAmount > $order->getAmount())
                        throw new AppException(400, "Refund cannot exceed order amount");

                    // if admin check otp else check signature
                    if($this->tokenStorage->getToken()->isAuthenticated()) {
                        if(!$this->auth->isGranted("ROLE_ADMIN"))
                            throw new AppException(403, "User must be admin");
                        $this->checkOTP();
                    }
                    else {
                        $signature_version = $currentRequest->request->get("signature_version", "");
                        $sent_signature = $currentRequest->request->get("signature", "");
                        if($signature_version !== "hmac_sha256_v1")
                            throw new AppException(400, "Invalid or not present signature version");
                        /** @var Pos $pos */
                        $pos = $order->getPos();

                        $dataToSign = [
                            "status" => PaymentOrder::STATUS_REFUNDED,
                            "signature_version" => $signature_version
                        ];

                        if($currentRequest->request->has("refund_amount"))
                            $dataToSign ["refund_amount"] = $refundAmount;

                        ksort($dataToSign);
                        $signaturePack = json_encode($dataToSign, JSON_UNESCAPED_SLASHES);

                        $calculated_signature = hash_hmac(
                            'sha256',
                            $signaturePack,
                            base64_decode($pos->getAccessSecret())
                        );

                        if($sent_signature !== $calculated_signature) {
                           throw new AppException(400, "Invalid or not present signature");
                        }
                    }

                    /*
                     *  This STATUS_REFUNDING status is to prevent recursivity when using IncomingController2
                     *  that does too many flush()
                     */
                    $order->skipStatusChecks(true);
                    $order->setStatus(PaymentOrder::STATUS_REFUNDING);

                    $receiverTx = $order->getPaymentTransaction();
                    /** @var DocumentManager $dm */
                    $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
                    $txRepo = $dm->getRepository(Transaction::class);
                    /** @var Transaction $senderTx */
                    $senderTx = $txRepo->findOneBy(['pay_out_info.txid' => $receiverTx->getPayInInfo()['txid']]);
                    /** @var Group $sender */
                    $sender = $args->getEntityManager()
                        ->getRepository(Group::class)
                        ->find($senderTx->getGroup());

                    /** @var IncomingController2 $tc */
                    $tc = $this->container->get('app.incoming_controller');

                    /** @var User $refunder */
                    $refunder = $order->getPos()->getAccount()->getKycManager();
                    $refundData = [
                        "address" => $sender->getRecAddress(),
                        "amount" => $refundAmount,
                        "concept" => "Refund for order {$order->getId()}",
                        "pin" => $refunder->getPin()
                    ];

                    $response = $tc->createTransaction(
                        $refundData,
                        1,
                        "out",
                        "rec",
                        $refunder->getId(),
                        $order->getPos()->getAccount(),
                        $currentRequest->getClientIp()
                    );

                    /*
                     * restoring back refunded status.
                     */
                    $order->setStatus(PaymentOrder::STATUS_REFUNDED);

                    $content = json_decode($response->getContent());
                    if($response->getStatusCode() == Response::HTTP_CREATED){
                        $id = $content->id;
                        /** @var Transaction $refundTx */
                        $refundTx = $txRepo->find($id);
                        $order->setRefundTransaction($refundTx);
                    }
                    else {
                        throw new AppException(
                            400,
                            "Error creating refund transaction: " . $content->message
                        );
                    }
                }
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args){
        $order = $args->getEntity();
        if($order instanceof PaymentOrder){
            $paymentUrl = $this->container->getParameter('pos_url');
            $order->setPaymentUrl($paymentUrl . "/{$order->getId()}");
        }
    }

    public function postLoad(LifecycleEventArgs $args){
        $this->postPersist($args);
    }

    public function prePersist(LifecycleEventArgs $args){
        $order = $args->getEntity();
        if($order instanceof PaymentOrder){

            $repo = $args->getEntityManager()->getRepository(Pos::class);
            /** @var Pos $pos */
            $pos = $repo->findOneBy(['access_key' => $order->getAccessKey()]);
            $order->setPos($pos);

            $ip = $this->requestStack->getCurrentRequest()->getClientIp();
            $order->setIpAddress($ip);

            /** @var FakeEasyBitcoinDriver $recDriver */
            $recDriver = $this->container->get('net.app.driver.easybitcoin.rec');
            $address = $recDriver->getnewaddress();
            $order->setPaymentAddress($address);
            $order->setStatus(PaymentOrder::STATUS_IN_PROGRESS);
        }
    }

    private function checkOTP() {
        /* check otp matches with current user */
        $currentRequest = $this->requestStack->getCurrentRequest();
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $otp = Google2FA::oath_totp($user->getTwoFactorCode());
        if($otp != $currentRequest->request->get('otp'))
            throw new AppException(403, "Invalid otp");
    }
}