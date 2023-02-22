<?php

namespace App\EventSubscriber\Kernel;

use App\Entity\ConfigurationSetting;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ConfigurationSettingsResponseSubscriber implements EventSubscriberInterface
{
    private $container;

    private $em;
    private LoggerInterface $logger;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, LoggerInterface $logger){

        $this->container = $container;
        $this->em = $em;
        $this->logger = $logger;
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        // TODO: Implement getSubscribedEvents() method.
        return [
            KernelEvents::RESPONSE => [
                ['onResponse', 10]
            ]
        ];
    }

    public function onResponse(FilterResponseEvent $event){
        if(!$event->isMasterRequest()){
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        if($request->getMethod() === 'GET' &&  $response->getStatusCode() === 200 && ($request->getPathInfo() === '/admin/v3/configuration_settings' || $request->getPathInfo() === '/admin/v3/configuration_setting' || $request->getPathInfo() === '/public/v3/configuration_settings' || $request->getPathInfo() === '/public/v3/configuration_setting') ){

            $content = json_decode($response->getContent(), true);
            $elements = $content['data']['elements'];

            $this->logger->info("CONFIGURATION_SETTINGS_RESPONSE -> start filter");
            $filtered_elements = [];

            foreach ($elements as $element){
                $setting = $this->em->getRepository(ConfigurationSetting::class)->findOneBy(array('name'=> $element['name']));
                if($setting && $setting->getPackage()->getPurchased()){
                    $filtered_elements[] = $element;
                }

            }
            $content['data']['elements'] = $filtered_elements;
            $response->setContent(json_encode($content));
        }
    }

}