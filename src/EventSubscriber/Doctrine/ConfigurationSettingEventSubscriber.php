<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\ConfigurationSetting;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ConfigurationSettingEventSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class ConfigurationSettingEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    protected $logger;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::preUpdate
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $setting = $args->getEntity();
        if($setting instanceof ConfigurationSetting) {
            if(!$setting->getPackage()->getPurchased()) throw new HttpException(403, 'Setting not llowed to change. Package '.$setting->getPackage()->getname().' not purchased');
        }
    }

}