<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
// src/AppBundle/EventListener/SearchIndexer.php
namespace App\FinancialApiBundle\EventListener;

use Blockchain\Exception\HttpError;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Document\Transaction;

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



    public function preUpdate(LifecycleEventArgs $args){
        $entity = $args->getDocument();
        $this->logger->info('(' . $entity->getGroup() . ') PRE-UPDATE Transaction_Listener');

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

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getDocument();
        $this->logger->info('(' . $entity->getGroup() . ') PRE-PERSIST transaction');

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
        $company = $em->getRepository('FinancialApiBundle:Group')->find($transaction->getGroup());
        $tier = $company->getTier();
        $this->logger->info('_checkMethodPermissions');
        $method = $this->container->get('net.app.'.$transaction->getType().'.'.$transaction->getMethod().'.v'.$transaction->getVersion());
        if($method->getMinTier() > $tier){
            throw new HttpException(403, 'You don\'t have the necessary permissions. You must to be Tier '.$method->getMinTier().' and your current Tier is '.$tier);
        }
        //check if method is available
        $statusMethod = $em->getRepository('FinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $transaction->getMethod(),
            'type'      =>  $transaction->getType()
        ));
        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Method temporally unavailable.');
    }
}