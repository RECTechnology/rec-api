<?php

namespace App\Controller\Open;

use App\Controller\RestApiController;
use App\DependencyInjection\Commons\AwardHandler;
use Symfony\Component\HttpFoundation\Request;

class DiscourseNotificationsController extends RestApiController
{

    public function notificate(Request $request, $version_number){

        $logger = $this->get('discourse.logger');
        $logger->info('Notification received');

        //TODO investigate how to send headers in headers in tests
        //fix temporal para compatibilidad entre tests y vida real
        if($request->headers->has('x-discourse-event') || $request->server->has('x-discourse-event')){

            if(!$request->headers->has('x-discourse-event')){
                $all = $request->server->all();
                $request->headers->add($all);
            }
            $logger->info("Is discourse notification", $request->headers->all());
            /** @var AwardHandler $awardHandler */
            $awardHandler = $this->get('net.app.commons.award_handler');

            $awardHandler->handleDiscourseNotification($request);

        }

        return $this->rest(
            200,
            "ok",
            "Request successful",
            array('status' => 'ok')
        );
    }

}