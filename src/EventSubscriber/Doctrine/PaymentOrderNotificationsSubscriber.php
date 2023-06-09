<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\PaymentOrder;
use App\Entity\PaymentOrderNotification;
use App\Entity\Pos;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * Class PaymentOrderNotificationsSubscriber
 * @package App\EventSubscriber\Doctrine
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
        $em = $args->getEntityManager();
        if($order instanceof PaymentOrder && $order->getPos()->getNotificationUrl() != null){

            if($order->getStatus() !== PaymentOrder::STATUS_REFUNDING){
                $existentNotifications = $em->getRepository(PaymentOrderNotification::class)->findBy(
                    array(
                        "payment_order" => $order
                    )
                );
                $existentNotification = false;
                foreach ($existentNotifications as $notification){
                    $content = $notification->getContent();
                    if($content['status'] === $order->getStatus()){
                        $existentNotification = true;
                    }
                }
                if(!$existentNotification){
                    $notification = new PaymentOrderNotification();
                    $notification->setPaymentOrder($order);

                    /** @var Pos $pos */
                    $pos = $order->getPos();
                    $notification->setUrl($pos->getNotificationUrl());

                    $amount = intval($order->getAmount());
                    $signature_version = "hmac_sha256_v1";

                    $nonce = round(microtime(true) * 1000, 0);
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

                }

            }

        }
        $em->flush();
    }

}