<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\Group;
use App\Entity\Iban;
use App\Exception\AppException;
use App\Exception\NoSuchTranslationException;
use App\Financial\Driver\LemonWayInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LemonUploadEventSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class LemonIbanEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    /**
     * LemonUploadEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
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

    /**
     * @param LifecycleEventArgs $args
     * @throws NoSuchTranslationException
     */
    public function prePersist(LifecycleEventArgs $args){
        $iban = $args->getEntity();
        if($iban instanceof Iban){

            /** @var LemonWayInterface $lemon */
            $lemon = $this->container->get('net.app.driver.lemonway.eur');

            /** @var Group $owner */
            $owner = $iban->getAccount();

            $resp = $lemon->callService(
                'RegisterIBAN',
                [
                    'wallet' => $owner->getCif(),
                    'holder' => $iban->getHolder(),
                    'bic' => $iban->getBic(),
                    'iban' => $iban->getNumber(),
                    'dom1' => $iban->getBankName(),
                    'dom2' => $iban->getBankAddress()
                ]
            );

            if(is_array($resp))
                throw new AppException(
                    400,
                    "LW error",
                    [
                        'property' => 'lemonway_error - REGISTERIBAN - ' . $resp['REGISTERIBAN']['ERROR'],
                        'message' => $resp['REGISTERIBAN']['MESSAGE']
                    ]
                );
            if($resp->E != null)
                throw new AppException(400, "LW error: {$resp->E}");

            if($resp->IBAN_REGISTER->ID == null)
                throw new AppException(503, "Bad LW response: " . json_encode($resp));
            $iban->setLemonReference($resp->IBAN_REGISTER->ID);
        }
    }
}