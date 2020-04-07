<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
        ];
    }

    public function prePersist(LifecycleEventArgs $args){
        $order = $args->getEntity();
        if($order instanceof PaymentOrder){
            /**
             * TODO:
             *  - check signature against pos
             */

            /** @var Pos $pos */
            $pos = $order->getPos();
            if(!$pos->getActive()){
                throw new AppException(400, "Related POS is not active, please contact administrator");
            }

            $ip = $this->requestStack->getCurrentRequest()->getClientIp();
            $order->setIpAddress($ip);

            /** @var FakeEasyBitcoinDriver $recDriver */
            $recDriver = $this->container->get('net.app.driver.easybitcoin.rec');
            $address = $recDriver->getnewaddress();
            $order->setPaymentAddress($address);
        }
    }

}