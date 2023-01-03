<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
namespace App\FinancialApiBundle\EventListener;

use App\FinancialApiBundle\DependencyInjection\App\Commons\GardenHandler;
use App\FinancialApiBundle\DependencyInjection\App\Interfaces\QualificationHandlerInterface;
use App\FinancialApiBundle\Financial\Currency;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Document\Transaction;

class TransactionListener
{
    protected $container;
    protected $logger;
    protected $permissionsHandler;
    private $crypto_currency;

    public function __construct(ContainerInterface $container, $permissionsHandler)
    {
        $this->container = $container;
        $this->logger = $this->container->get('transaction.logger');
        $this->permissionsHandler = $permissionsHandler;
        $this->crypto_currency = $container->getParameter('crypto_currency');

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
        }

    }

    public function postUpdate(LifecycleEventArgs $args){
        $entity = $args->getDocument();
        $this->logger->info('(' . $entity->getGroup() . ') Post-UPDATE Transaction_Listener');

        $entityManager = $args->getDocumentManager();
        $uow = $entityManager->getUnitOfWork();

        if ($entity instanceof Transaction) {
            $changeset = $uow->getDocumentChangeSet($entity);
            /** @var GardenHandler $gardenHandler */
            $gardenHandler = $this->container->get('net.app.commons.garden_handler');
            if($entity->getType() === Transaction::$TYPE_OUT && $entity->getCurrency() === $this->crypto_currency){

                if(isset($changeset['status'])){
                    if($changeset['status'][1] === Transaction::$STATUS_SUCCESS){
                        //create a qualification battery (payments in rec)
                        /** @var QualificationHandlerInterface $qualificationHandler */
                        $qualificationHandler = $this->container->get('net.app.commons.qualification_handler');
                        $qualificationHandler->createQualificationBattery($entity);

                        $gardenHandler->updateGarden(GardenHandler::ACTION_BUY);

                    }
                }
            }

            if($entity->getType() === Transaction::$TYPE_IN && $entity->getMethod() === 'lemonway'){
                if(isset($changeset['status'])) {
                    if ($changeset['status'][1] == Transaction::$STATUS_SUCCESS) {

                        $gardenHandler->updateGarden(GardenHandler::ACTION_RECHARGE);
                    }
                }
            }
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