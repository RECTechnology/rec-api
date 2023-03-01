<?php

namespace App\Controller\Open;

use App\Entity\MailingDelivery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\RestApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MailgunController extends RestApiController {

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function webhookAction(Request $request, EntityManagerInterface $em) {
        $repo = $em->getRepository(MailingDelivery::class);
        $signature = $request->get('signature'); //TODO: check webhook signature
        $event = $request->get('event-data');
        $delivery = $repo->findOneBy(['message_ref' => $event['message']['headers']['message-id']]);
        if($delivery){
            $delivery->setStatus($event['event']);
            if ($event['event'] == MailingDelivery::STATUS_FAILED){
                $delivery->setFailureReason($event['delivery-status']['description'] . $event['delivery-status']['message']);
            }
            $em->persist($delivery);
            $em->flush();
        }

        return $this->rest(200, "success", "Webhook processed successfully");
    }
}