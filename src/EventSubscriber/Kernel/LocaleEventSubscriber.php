<?php

namespace App\EventSubscriber\Kernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LocaleEventSubscriber
 * @package App\EventSubscriber
 */
class LocaleEventSubscriber implements EventSubscriberInterface {

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public static function getSubscribedEvents() {
        return [
            KernelEvents::REQUEST => ['setRequestLocale', 10],
        ];
    }

    public function setRequestLocale(GetResponseEvent $event){
        if($event->isMasterRequest()){
            $request = $event->getRequest();
            $locale = $this->getRequestLocale($request);
            $request->setLocale($locale);
        }
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getRequestLocale(Request $request){
        $headers = $request->headers;
        if(in_array($request->getMethod(), ['POST', 'PUT'])){
            return $headers->get('content-language', $request->getDefaultLocale());
        }
        return $headers->get('accept-language', $request->getDefaultLocale());
    }

}