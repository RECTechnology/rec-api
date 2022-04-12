<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Exception\AttemptToChangeStatusException;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DiscourseEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class DiscourseEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::preUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $account = $args->getEntity();
        if($account instanceof Group){
            if ($args->hasChangedField("rezero_b2b_access")){
                if($args->getNewValue("rezero_b2b_access") == Group::ACCESS_STATE_GRANTED){
                    //TODO check if discourse user exist
                    if(!$account->getRezeroB2bUserId()){
                        $resp = $this->setUpDiscourseAccount($account);

                        if(isset($resp['registered']) && $resp['registered'] === true){
                            $account->setRezeroB2bUserId($resp['user_id']);
                            if($resp['api_key']){
                                $account->setRezeroB2bApiKey($resp['api_key']);
                                /** @var DiscourseApiManager $discourseManager */
                                $discourseManager = $this->getDiscourseManager();
                                $discourseManager->subscribeToNewsCategory($account);
                            }
                        }
                    }
                }
            }

            if($account->getRezeroB2bApiKey()){
                if($args->hasChangedField("name")){
                    /** @var DiscourseApiManager $discourseManager */
                    $discourseManager = $this->getDiscourseManager();
                    $discourseManager->updateName($account, $args->getNewValue("name"));

                }

                if($args->hasChangedField("rezero_b2b_username")){
                    /** @var DiscourseApiManager $discourseManager */
                    $discourseManager = $this->getDiscourseManager();
                    $discourseManager->updateUsername($account, $args->getOldValue("rezero_b2b_username"), $args->getNewValue("rezero_b2b_username"));

                }
            }

        }
    }

    private function setUpDiscourseAccount(Group $account){
        /** @var DiscourseApiManager $discourseManager */
        $discourseManager = $this->container->get('net.app.commons.discourse.api_manager');

        $response = array();

        $registerResponse = $discourseManager->register($account);

        if(isset($registerResponse["success"]) && $registerResponse['success'] === true){
            $response['registered'] = true;
            $response['user_id'] = $registerResponse['user_id'];
            $key = $discourseManager->generateApiKeys($account);

            if($key !== 'error'){
                $response['api_key'] = $key;
            }else{
                $response['api_key'] = false;
            }
        }else{
            throw new HttpException(400, $registerResponse['message']);
        }

        return $response;
    }

    private function getDiscourseManager(){
        /** @var DiscourseApiManager $discourseManager */
        return $this->container->get('net.app.commons.discourse.api_manager');
    }

}