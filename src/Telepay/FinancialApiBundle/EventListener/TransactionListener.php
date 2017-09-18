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
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class TransactionListener
{
    protected $container;
    protected $logger;
    protected $permissionsHandler;

    public function __construct(ContainerInterface $container, $permissionsHandler)
    {
        $this->container = $container;
        $this->logger = $this->container->get('transaction.logger');
        $this->permissionsHandler = $permissionsHandler;

    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getDocument();
        $this->logger->info('POST-UPDATE Transaction_Listener');

        $entityManager = $args->getDocumentManager();
        $uow = $entityManager->getUnitOfWork();

    }

    public function preUpdate(LifecycleEventArgs $args){

        $entity = $args->getDocument();
        $this->logger->info('POST-UPDATE Transaction_Listener');

        $entityManager = $args->getDocumentManager();
        $uow = $entityManager->getUnitOfWork();

        if ($entity instanceof Transaction) {

            //check tier to get permissions to method only for in and out transactions
            if(($entity->getType() == 'in' || $entity->getType() == 'out')
                && $entity->getMethod() != 'wallet_to_wallet'){
//                $this->_checkMethodPermissions($entity, $documentManager);
                $this->permissionsHandler->checkMethodPermissions($entity);
            }
            return;
        }

    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getDocument();
        $this->logger->info('POST-INSERT transaction');

        $entityManager = $args->getDocumentManager();



    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getDocument();
        $this->logger->info('PRE-INSERT transaction');

        $documentManager = $args->getDocumentManager();

        if ($entity instanceof Transaction) {

            //check tier to get permissions to method only for in and out transactions
            if(($entity->getType() == 'in' || $entity->getType() == 'out')
                && $entity->getMethod() != 'wallet_to_wallet'){
//                $this->_checkMethodPermissions($entity, $documentManager);
                $this->permissionsHandler->checkMethodPermissions($entity);
            }
            return;
        }

    }

    private function _checkMethodPermissions(Transaction $transaction, $dm){

        $em = $this->container->get('doctrine')->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
        $tier = $company->getTier();
        $this->logger->info('_checkMethodPermissions');
        $method = $this->container->get('net.telepay.'.$transaction->getType().'.'.$transaction->getMethod().'.v'.$transaction->getVersion());
        if($company->getGroupCreator()->getid() == $this->container->getParameter('default_company_creator_commerce_android_fair')){
            //is fairpay user
            if($method->getCname() != 'fac'){
                throw new HttpException(403, 'You don\'t have the necessary permissions. You are fairpay user');
            }
        }

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