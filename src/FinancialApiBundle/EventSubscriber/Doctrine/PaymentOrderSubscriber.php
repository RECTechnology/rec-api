<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class PaymentOrderSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class PaymentOrderSubscriber implements EventSubscriber {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestStack $requestStack */
    private $requestStack;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param EntityManagerInterface $em
     * @param RequestStack $requestStack
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, ContainerInterface $container)
    {
        $this->em = $em;
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

        ];
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

            $repo = $this->em->getRepository(Pos::class);
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

}