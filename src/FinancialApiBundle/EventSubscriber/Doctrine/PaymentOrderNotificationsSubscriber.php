<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\PaymentOrderNotification;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class PaymentOrderNotificationsSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class PaymentOrderNotificationsSubscriber implements EventSubscriber {

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args){
        $this->postUpdate($args);
    }

    public function postUpdate(LifecycleEventArgs $args){
        $order = $args->getEntity();
        if($order instanceof PaymentOrder && $order->getPos()->getNotificationUrl() != null){
            $em = $args->getEntityManager();
            $existentNotification = $em->getRepository(PaymentOrderNotification::class)->findOneBy(
                array(
                    "status" => $order->getStatus(),
                    "payment_order" => $order
                )
            );
            if(!$existentNotification){
                $notification = new PaymentOrderNotification();
                $notification->setPaymentOrder($order);

                /** @var Pos $pos */
                $pos = $order->getPos();
                $notification->setUrl($pos->getNotificationUrl());

                $amount = intval($order->getAmount());
                $signature_version = "hmac_sha256_v1";
                $now = new \DateTime();
                $nonce = $now->getTimestamp();
                $dataToSign = [
                    "payment_order" => $order->getId(),
                    'reference' => $order->getReference(),
                    "amount" => $amount,
                    "time" => $order->getUpdated()->format('c'),
                    "status" => $order->getStatus(),
                    "nonce" => $nonce,
                    "signature_version" => $signature_version
                ];

                ksort($dataToSign);
                $signaturePack = json_encode($dataToSign, JSON_UNESCAPED_SLASHES);

                $signature = hash_hmac('sha256', $signaturePack, base64_decode($pos->getAccessSecret()));

                $notification->setContent($dataToSign + ["signature" => $signature]);
                $em = $args->getEntityManager();
                $em->persist($notification);
                $em->flush();
            }

        }
    }

}