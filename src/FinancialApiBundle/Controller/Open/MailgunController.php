<?php

namespace App\FinancialApiBundle\Controller\Open;

use App\FinancialApiBundle\Entity\MailingDelivery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Controller\RestApiController;
use Symfony\Component\HttpFoundation\Response;

class MailgunController extends RestApiController {

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return void
     */
    public function webhookAction(Request $request, EntityManagerInterface $em) {
        $repo = $em->getRepository(MailingDelivery::class);
        $signature = $request->get('signature');
        $event = $request->get('event-data');
        $delivery = $repo->find($event['message']['headers']['mailing-delivery-id']);
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
    }
}