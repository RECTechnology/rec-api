<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
// src/AppBundle/EventListener/SearchIndexer.php
namespace Telepay\FinancialApiBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telepay\FinancialApiBundle\Entity\AccessToken;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\KYC;
use Telepay\FinancialApiBundle\Entity\KYCCompanyValidations;
use Telepay\FinancialApiBundle\Entity\User;

class KycListener
{
    protected $container;
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->info('POST-UPDATE Kyc_Listener');

        $entityManager = $args->getEntityManager();
        $uow = $entityManager->getUnitOfWork();

        if ($entity instanceof Accesstoken) {
            $user = $entity->getUser();
            $user->setLastLogin(new \DateTime);
            $entityManager->persist($user);
            $entityManager->flush();
            return;
        }


    }

    public function preUpdate(LifecycleEventArgs $args){

    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->info('POST-INSERT');

        $entityManager = $args->getEntityManager();

        if ($entity instanceof Group) {
            $entity->setTier(0);
            return;
        }


    }

    private function _sendEmail($subject, $body, $to, $action){
        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';

        if($action == 'user_kyc'){
            $template = 'TelepayFinancialApiBundle:Email:KYCUpdate.html.twig';
        }elseif($action == 'company_kyc'){}else{
            $template = 'TelepayFinancialApiBundle:Email:KYCUpdate.html.twig';
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        $body
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }


}