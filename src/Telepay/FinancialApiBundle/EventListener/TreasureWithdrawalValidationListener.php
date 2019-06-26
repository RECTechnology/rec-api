<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/19/19
 * Time: 5:42 PM
 */

namespace Telepay\FinancialApiBundle\EventListener;


use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\TreasureWithdrawalAttempt;
use Telepay\FinancialApiBundle\Entity\TreasureWithdrawalValidation;

class TreasureWithdrawalValidationListener {

    /** @var ContainerInterface $container */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $validation) {
            if($validation instanceof TreasureWithdrawalValidation) {

                $changes = $uow->getEntityChangeSet($validation);
                if(isset($changes['approved'])) {
                    /** @var TreasureWithdrawalAttempt $attempt */
                    $attempt = $validation->getAttempt();
                    if ($attempt->isApproved() and !$attempt->isSent()) {
                        $id_group_root = $this->container->getParameter('id_group_root');
                        /** @var Group $root */
                        $root = $em->getRepository('TelepayFinancialApiBundle:Group')->find($id_group_root);
                        $resp = $this->sendRecFromTreasure($attempt->getAmount(), $root);

                        # if received is ok
                        if (strpos($resp, 'created') !== false) {

                            if (preg_match("/ID: ([a-zA-Z0-9]+)/", $resp, $matches)) {
                                $txId = $matches[1];

                                /** @var DocumentManager $odm */
                                $odm = $this->container->get('doctrine_mongodb.odm.document_manager');
                                $txRepo = $odm->getRepository("TelepayFinancialApiBundle:Transaction");
                                /** @var Transaction $tx */
                                $tx = $txRepo->find($txId);

                                $attempt->setTransaction($tx);
                                $this->save($attempt, $em, $uow);
                                return;
                            }
                        }
                    }
                    throw new \LogicException("Some error occurred when trying to send RECs, please retry later");
                }
            }
        }
    }

    /**
     * @param $amount
     * @param Group $root
     * @return mixed
     */
    private function sendRecFromTreasure($amount, Group $root){

        //send recs
        $method = $this->container->get('net.telepay.out.rec.v1');
        $treasure_address = $this->container->getParameter('treasure_address');

        $id_user_root = $root->getKycManager()->getId();

        $payment_info['amount'] = $amount * 1e8;
        $payment_info['orig_address'] = $treasure_address;
        $payment_info['orig_nif'] = 'some_admins';
        $payment_info['orig_group_nif'] = $root->getCif();
        $payment_info['orig_group_public'] = true;
        $payment_info['orig_key'] = $root->getKeyChain();
        $payment_info['dest_address'] = $root->getRecAddress();
        $payment_info['dest_group_nif'] = $root->getCif();
        $payment_info['dest_group_public'] = false;
        $payment_info['dest_key'] = $root->getKeyChain();
        $payment_info = $method->send($payment_info);
        $txid = $payment_info['txid'];

        if(isset($payment_info['error'])){
            throw new \LogicException($payment_info['error']);
        }

        $params = array(
            'amount' => $amount * 1e8,
            'concept' => "Treasure withdrawal",
            'address' => $root->getRecAddress(),
            'txid' => $txid,
            'sender' => '0'
        );
        return $this->container->get('app.incoming_controller')
            ->createTransaction($params, 1, 'in', 'rec', $id_user_root, $root, '127.0.0.1');
    }

    /**
     * @param $entity
     * @param EntityManagerInterface $em
     * @param UnitOfWork $uow
     */
    private function save($entity, EntityManagerInterface $em, UnitOfWork $uow){
        $em->persist($entity);
        $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(ClassUtils::getClass($entity)), $entity);
    }
}