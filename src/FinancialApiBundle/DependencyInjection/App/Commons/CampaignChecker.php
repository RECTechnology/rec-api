<?php

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\DependencyInjection\App\Interfaces\QualificationHandlerInterface;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Qualification;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CampaignChecker
{

    private $doctrine;

    private $container;

    public function __construct($doctrine, ContainerInterface $container)
    {
        $this->doctrine = $doctrine;
        $this->container = $container;
    }

    public function isCampaignTx(Transaction $tx, $campaignName){
        $em = $this->getEntityManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => $campaignName]);

        if(!$campaign) throw new HttpException(404, "Campaign not found");

        if($tx->getMethod() === "rec"){
            $id_group_root = $this->container->getParameter('id_group_root');
            if ($tx->getType() == Transaction::$TYPE_OUT and $tx->getGroup() != $id_group_root) {
                $sender = $this->getSenderFromTx($tx);
                $receiver = $this->getShopFromTx($tx);
                $sender_in_campaign = in_array($sender, $campaign->getAccounts()->toArray());
                $receiver_in_campaign = in_array($receiver, $campaign->getAccounts()->toArray());
                if($sender_in_campaign) {
                    if (!$receiver_in_campaign) {
                        throw new HttpException(Response::HTTP_BAD_REQUEST, "Receiver account not in Campaign");
                    }
                }
                if($receiver_in_campaign) {
                    if(!$sender_in_campaign) {
                        throw new HttpException(Response::HTTP_BAD_REQUEST, "Sender account not in Campaign");
                    }
                }
                return true;
            }
        }

        return false;
    }

    public function isShopInCampaign(Transaction $tx, $campaignName){
        $em = $this->getEntityManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => $campaignName]);

        if(!$campaign) throw new HttpException(404, "Campaign not found");

        if($tx->getMethod() === "rec"){
            $id_group_root = $this->container->getParameter('id_group_root');
            if ($tx->getType() == Transaction::$TYPE_OUT and $tx->getGroup() != $id_group_root) {

                $receiver = $this->getShopFromTx($tx);
                $receiver_in_campaign = in_array($receiver, $campaign->getAccounts()->toArray());
                if (!$receiver_in_campaign) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    private function getEntityManager(){
        return $this->doctrine->getManager();
    }

    private function getSenderFromTx(Transaction $tx){
        $em = $this->getEntityManager();
        return $em->getRepository(Group::class)->find($tx->getGroup());
    }

    private function getShopFromTx(Transaction $tx){
        $em = $this->getEntityManager();
        $shop = $em->getRepository(Group::class)->findOneBy(array('rec_address' => $tx->getPayOutInfo()['address']));

        if(!$shop){
            //buscar en paymentOrders
            $paymentOrder = $em->getRepository(PaymentOrder::class)->findOneBy(array(
                'payment_address' => $tx->getPayOutInfo()['address']
            ));

            $shop = $paymentOrder->getPos()->getAccount();
        }

        return $shop;
    }

}