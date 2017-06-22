<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
// src/AppBundle/EventListener/SearchIndexer.php
namespace Telepay\FinancialApiBundle\EventListener;

use Blockchain\Exception\HttpError;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\AccessToken;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\KYC;
use Telepay\FinancialApiBundle\Entity\User;

class TransactionListener
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
        $this->logger->info('POST-UPDATE Transaction_Listener');

        $entityManager = $args->getEntityManager();
        $uow = $entityManager->getUnitOfWork();

    }

    public function preUpdate(LifecycleEventArgs $args){

    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->info('POST-INSERT transaction');

        $entityManager = $args->getEntityManager();

    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->info('PRE-INSERT transaction');

        $entityManager = $args->getEntityManager();

        if ($entity instanceof Transaction) {

            //check tier to get permissions to method only for in and out transactions
            if($entity->getType() == 'in' || $entity->getType() == 'out'){
                $this->_checkMethodPermissions($entity, $entityManager);
            }
            return;
        }

    }

    private function _checkMethodPermissions(Transaction $transaction, EntityManager $em){

        $company = $em->getRepository('telepayFinancialApiBundle:Group')->find($transaction->getGroup());
        $tier = $company->getTier();

        $method = $method = $this->get('net.telepay.'.$transaction->getType().'.'.$transaction->getMethod().'.v'.$transaction->getVersion());

        if($method->getMinTier() > $tier){
            throw new HttpException(403, 'You don\'t have the necessary permissions. You must to be Tier '.$method->getMinTier().' and your current Tier is '.$tier);
        }

        //check if method is available
        $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $transaction->getMethod(),
            'type'      =>  $transaction->getType()
        ));

        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Method temporally unavailable.');

    }


    private function _sendEmail($subject, $to, $companies, $kyc, $tier, $action){
        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';

        $template = 'TelepayFinancialApiBundle:Email:KYCUpdate.html.twig';

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'companies' =>  $companies,
                            'kyc'   =>  $kyc,
                            'tier'  =>  $tier,
                            'action'    =>  $action
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

}