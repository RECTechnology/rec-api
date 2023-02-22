<?php

namespace App\EventSubscriber\Doctrine;

use App\Controller\Transactions\IncomingController2;
use App\Document\Transaction;
use App\Entity\Group;
use App\Entity\PaymentOrder;
use App\Entity\Pos;
use App\Entity\User;
use App\Exception\AppException;
use App\Exception\AttemptToChangeStatusException;
use App\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PaymentOrderSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class PaymentOrderSubscriber implements EventSubscriber {

    /** @var RequestStack $requestStack */
    private $requestStack;

    /** @var ContainerInterface $container */
    private $container;

    private $crypto_currency;
    private $recDriver;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param RequestStack $requestStack
     * @param ContainerInterface $container
     * @param FakeEasyBitcoinDriver $recDriver
     */
    public function __construct(RequestStack $requestStack, ContainerInterface $container, FakeEasyBitcoinDriver $recDriver)
    {
        $this->requestStack = $requestStack;
        $this->container = $container;
        $this->crypto_currency = $container->getParameter('crypto_currency');
        $this->recDriver = $recDriver;
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
                    if($args->getOldValue("status") == PaymentOrder::STATUS_IN_PROGRESS){
                        throw new AttemptToChangeStatusException("Changing status is not allowed");
                    }
                    $currentRequest = $this->requestStack->getCurrentRequest();

                    $refundAmount = $currentRequest->request->get("refund_amount", $order->getAmount());
                    if($refundAmount > $order->getAmount())
                        throw new AppException(400, "Refund cannot exceed order amount");

                    /*
                     *  This STATUS_REFUNDING status is to prevent recursivity when using IncomingController2
                     *  that does too many flush()
                     */
                    $order->skipStatusChecks(true);
                    $order->setStatus(PaymentOrder::STATUS_REFUNDING);

                    //In tx received in shop
                    $paymentInTx = $order->getPaymentTransaction();
                    /** @var DocumentManager $dm */
                    $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
                    $txRepo = $dm->getRepository(Transaction::class);
                    /** @var Transaction $senderTx */
                    //out tx sent by user
                    $paymentOutTx = $txRepo->findOneBy(['pay_out_info.txid' => $paymentInTx->getPayInInfo()['txid']]);
                    //account from where the original payment was done
                    /** @var Group $payerAccount */
                    $payerAccount = $args->getEntityManager()
                        ->getRepository(Group::class)
                        ->find($paymentOutTx->getGroup());

                    /** @var IncomingController2 $tc */
                    $tc = $this->container->get('app.incoming_controller3');

                    /** @var User $refunder */
                    $refunder = $order->getPos()->getAccount()->getKycManager();
                    $refundData = [
                        "address" => $payerAccount->getRecAddress(),
                        "amount" => $refundAmount,
                        "concept" => "Refund for order {$order->getId()}",
                        "pin" => $refunder->getPin()
                    ];

                    $response = $tc->createTransaction(
                        $refundData,
                        1,
                        "out",
                        strtolower($this->crypto_currency),
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
                        $order->setRefundedAmount($refundAmount);
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

            if($this->requestStack->getCurrentRequest()){
                $ip = $this->requestStack->getCurrentRequest()->getClientIp();
                $order->setIpAddress($ip);
            }

            $address = $this->recDriver->getnewaddress();
            $order->setPaymentAddress($address);
            $order->setStatus(PaymentOrder::STATUS_IN_PROGRESS);
        }
    }

}