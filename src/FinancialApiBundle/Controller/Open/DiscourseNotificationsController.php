<?php

namespace App\FinancialApiBundle\Controller\Open;

use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\DependencyInjection\App\Commons\AwardHandler;
use Symfony\Component\HttpFoundation\Request;

class DiscourseNotificationsController extends RestApiController
{

    public function notificate(Request $request, $version_number){

        if($request->server->has('x-discourse-event')){

            /** @var AwardHandler $awardHandler */
            $awardHandler = $this->get('net.app.commons.award_handler');

            $awardHandler->handleDiscourseNotification($request);

            return $this->restV2(
                200,
                "ok",
                "Request successful",
                array('status' => 'ok')
            );

        }
    }

}