<?php

namespace App\FinancialApiBundle\Controller\Open;

use App\FinancialApiBundle\Entity\MailingDelivery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Controller\RestApiController;
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
        $signature = $request->get('signature');
        $event = $request->get('event-data');
        $delivery = $repo->findOneBy(['message_ref' => $event['message']['headers']['message-id']]);
        if(!$delivery)
            throw new HttpException(Response::HTTP_NOT_FOUND, "Message not found");
        switch ($event['event']){
            case 'delivered':
                $delivery->setStatus(MailingDelivery::STATUS_DELIVERED);
                break;
            case 'opened':
                $delivery->setStatus(MailingDelivery::STATUS_OPENED);
                break;
            case 'unsubscribed':
                $delivery->setStatus(MailingDelivery::STATUS_UNSUBSCRIBED);
                break;
            default:
        }
        $em->persist($delivery);
        $em->flush();
        return $this->restV2(200, "success", "Webhook processed successfully");
    }
}