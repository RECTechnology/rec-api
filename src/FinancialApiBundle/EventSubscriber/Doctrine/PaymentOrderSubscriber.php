<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Exception\AttemptToChangeStatusException;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
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
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class PaymentOrderSubscriber implements EventSubscriber {

    /** @var RequestStack $requestStack */
    private $requestStack;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param RequestStack $requestStack
     * @param ContainerInterface $container
     */
    public function __construct(RequestStack $requestStack, ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->container = $container;
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

                    $receiverTx = $order->getPaymentTransaction();
                    /** @var DocumentManager $dm */
                    $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
                    $txRepo = $dm->getRepository(Transaction::class);
                    /** @var Transaction $senderTx */
                    $senderTx = $txRepo->findOneBy(['pay_out_info.txid' => $receiverTx->getPayInInfo()['txid']]);
                    /** @var Group $sender */
                    $receiver = $args->getEntityManager()
                        ->getRepository(Group::class)
                        ->find($receiverTx->getGroup());

                    /** @var IncomingController2 $tc */
                    $tc = $this->container->get('app.incoming_controller3');

                    /** @var User $refunder */
                    $refunder = $order->getPos()->getAccount()->getKycManager();
                    $refundData = [
                        "address" => $receiver->getRecAddress(),
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

            /** @var FakeEasyBitcoinDriver $recDriver */
            $recDriver = $this->container->get('net.app.driver.easybitcoin.rec');
            $address = $recDriver->getnewaddress();
            $order->setPaymentAddress($address);
            $order->setStatus(PaymentOrder::STATUS_IN_PROGRESS);
        }
    }

}