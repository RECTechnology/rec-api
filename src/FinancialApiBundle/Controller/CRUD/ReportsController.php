<?php


namespace App\FinancialApiBundle\Controller\CRUD;


use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stubs\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Templating\EngineInterface;


class ReportsController extends AccountsController
{
    /**
     * @param EngineInterface $templating
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     */
    public function reportMassiveTransactionsAction(EngineInterface $templating, Request $request, $role, $id)
    {
        $this->checkPermissions($role, BaseApiV2Controller::CRUD_CREATE);

        $admin_user = $this->get('security.token_storage')->getToken()->getUser();
        $group = $admin_user->getActiveGroup();
        if ($group->getEmail() == null or $group->getEmail() == ''){
            throw new HttpException(
                400, "You must assign your user an email to send the report.");
        }

        /** @var DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManager();

        $repoDcd = $em->getRepository(DelegatedChangeData::class);

        $dc_dcds = $repoDcd->findBy(["delegated_change" => $id]);


        $tmpLocation = '/tmp/' . uniqid("massive_transactions_") . ".tmp.csv";
        $fp = fopen($tmpLocation, 'w');
        $headers = array('TOTAL', 'CURRENCY', 'ID', 'STATUS', 'TYPE', 'CREATED', 'METHOD', 'Account CIF', 'Account ID');
        fputcsv($fp, $headers);

        foreach ($dc_dcds as $dcd) {
            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('id')->equals($dcd->getTransactionRef())->getQuery();

            $transaction = $qb->toArray()[$dcd->getTransactionRef()];
            $user = $em->getRepository(User::class)->find($transaction->getUser());
            $transaction_data = array(
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getId(),
                $transaction->getStatus(),
                $transaction->getType(),
                $transaction->getCreated()->format('Y-m-d H:i:s'),
                $transaction->getMethod(),
                $dcd->getAccount()->getCif(),
                $dcd->getAccount()->getId()
            );

            fputcsv($fp, $transaction_data);
        }
        fclose($fp);

        $this->scheduleMailing($group, $em, $tmpLocation);

        return new Response(
            "No content",
            204,
            []
        );
    }

    /**
     * @param EngineInterface $templating
     * @param Request $request
     * @param $role
     * @return Response
     */
    public function reportLTABAction(EngineInterface $templating, Request $request, $role)
    {

        $this->checkPermissions($role, BaseApiV2Controller::CRUD_CREATE);

        /** @var DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManager();
        $repoGroup = $em->getRepository(Group::class);

        $_since = $request->request->get("since", "0");
        $_to = $request->request->get("to", "0");

        $campaign = $em->getRepository(Campaign::class)->findOneBy(["name" => Campaign::BONISSIM_CAMPAIGN_NAME]);

        if ($_since != "0") {
            $since = new \MongoDate(strtotime($_since . ' 00:00:00'));
        } else {
            $since = new \MongoDate($campaign->getInitDate()->getTimestamp());
        }

        if ($_to != "0") {
            $to = new \MongoDate(strtotime($_to . ' 23:59:59'));
        } else {
            $to = new \MongoDate($campaign->getEndDate()->getTimestamp());
        }

        $crypto_currency = $this->getParameter('crypto_currency');
        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
            ->field('method')->equals(strtolower($crypto_currency))
            ->field('type')->equals('out')
            ->field('status')->equals('success')
            ->field('created')->gte($since)
            ->field('created')->lte($to)
            ->getQuery();

        $transactions = $qb->toArray();
        $company_accounts = [];
        $private_accounts = [];
        $cert1_transactions = [];
        $cert2_transactions = [];
        $cert3_transactions = [];
        $total_c1_amount = 0;
        $total_c2_amount = 0;
        $total_c3_amount = 0;
        $company_c3_accounts = [];
        $private_c3_accounts = [];

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $sender = $repoGroup->find($transaction->getGroup());
            if ($transaction->getPayOutInfo()['name_receiver'] == Campaign::BONISSIM_CAMPAIGN_NAME and
                $transaction->getInternal()) {
                if ($sender->getType() == Group::ACCOUNT_TYPE_ORGANIZATION and sizeof($sender->getCampaigns())) {
                    $receiver_c2 = $repoGroup->findOneBy(['rec_address' => $transaction->getPayOutInfo()['address']]);
                    //search cert2 transactions
                    if ($receiver_c2 and $receiver_c2->getType() == Group::ACCOUNT_TYPE_PRIVATE and sizeof($receiver_c2->getCampaigns())) {
                        $transaction_data = array(
                            $sender->getId(),
                            $sender->getType(),
                            $transaction->getCreated()->format('Y-m-d H:i:s'),
                            $transaction->getId(),
                            $receiver_c2->getId(),
                            $receiver_c2->getType(),
                            'Bonificació',
                            $transaction->getService(),
                            $transaction->getAmount()
                        );
                        array_push($cert2_transactions, $transaction_data);
                        array_push($company_accounts, $sender->getId());
                        array_push($private_accounts, $receiver_c2->getId());
                        $total_c2_amount += $transaction->getAmount();

                        //search cert1 transactions
                        foreach ($transactions as $trans) {
                            if (round(($trans->getAmount() / 100) * Campaign::PERCENTAGE, 2) ==
                                round($transaction->getAmount(), 2)) {
                                $receiver_c1 = $repoGroup->findOneBy(['rec_address' => $trans->getPayOutInfo()['address']]);
                                if ($receiver_c1->getId() == $sender->getId()) {
                                    $sender_c1 = $repoGroup->find($trans->getGroup());
                                    if ($sender_c1->getKycManager()->getId() == $receiver_c2->getKycManager()->getId()) {
                                        $transaction_data = array(
                                            $sender_c1->getId(),
                                            $sender_c1->getType(),
                                            $trans->getCreated()->format('Y-m-d H:i:s'),
                                            $trans->getId(),
                                            $receiver_c1->getId(),
                                            $receiver_c1->getType(),
                                            'Compra bonificable',
                                            $trans->getService(),
                                            $trans->getAmount()
                                        );
                                        array_push($cert1_transactions, $transaction_data);
                                        $total_c1_amount += $trans->getAmount();
                                    }
                                }
                            }
                        }
                    }
                }
            }
            //search cert3 transactions
            if ($sender->getType() == Group::ACCOUNT_TYPE_PRIVATE and sizeof($sender->getCampaigns())) {
                $receiver_c3 = $repoGroup->findOneBy(['rec_address' => $transaction->getPayOutInfo()['address']]);
                if ($receiver_c3 and $receiver_c3->getType() == Group::ACCOUNT_TYPE_PRIVATE) {
                    $tx_type = 'Transfer';
                } else {
                    $tx_type = 'Payment';
                    array_push($company_c3_accounts, $receiver_c3->getId());
                }
                $transaction_data = array(
                    $sender->getId(),
                    $sender->getType(),
                    $transaction->getCreated()->format('Y-m-d H:i:s'),
                    $transaction->getId(),
                    $receiver_c3->getId(),
                    $receiver_c3->getType(),
                    'Enviado desde LTAB',
                    $transaction->getService(),
                    $transaction->getAmount(),
                    $tx_type
                );
                array_push($cert3_transactions, $transaction_data);
                array_push($private_c3_accounts, $sender->getId());
                $total_c3_amount += $transaction->getAmount();
            }
        }

        $company_accounts = array_unique($company_accounts);
        $private_accounts = array_unique($private_accounts);
        $company_c3_accounts = array_unique($company_c3_accounts);
        $private_c3_accounts = array_unique($private_c3_accounts);

        $ltab_account = $repoGroup->find($campaign->getCampaignAccount());

        if ($cert2_transactions) {
            $tmpLocation = '/tmp/' . uniqid("cert2_") . ".tmp.csv";
            $fp = fopen($tmpLocation, 'w');
            $headers = array('Id sender', 'sender type', 'fecha', 'transacción id', 'id receiver', 'receiver type',
                'Concepto', 'servicio', 'cantidad R');
            fputcsv($fp, $headers);

            foreach ($cert2_transactions as $transaction) {
                fputcsv($fp, $transaction);
            }
            fputcsv($fp, array('Total company accounts:', sizeof($company_accounts)));
            fputcsv($fp, array('Total private accounts:', sizeof($private_accounts)));
            fputcsv($fp, array('Total REC ammount:', $total_c2_amount));
            fputcsv($fp, array('Total transactions:', sizeof($cert2_transactions)));
            fclose($fp);

            $this->scheduleMailing($ltab_account, $em, $tmpLocation);
        }

        if ($cert1_transactions) {
            $tmpLocation = '/tmp/' . uniqid("cert1_") . ".tmp.csv";
            $fp = fopen($tmpLocation, 'w');
            $headers = array('Id sender', 'sender type', 'fecha', 'transacción id', 'id receiver', 'receiver type',
                'Concepto', 'servicio', 'cantidad R');
            fputcsv($fp, $headers);

            foreach ($cert1_transactions as $transaction) {
                fputcsv($fp, $transaction);
            }
            fputcsv($fp, array('Total users:', sizeof($private_accounts)));
            fputcsv($fp, array('Total REC ammount:', $total_c1_amount));
            fputcsv($fp, array('Total transactions:', sizeof($cert1_transactions)));
            fclose($fp);

            $this->scheduleMailing($ltab_account, $em, $tmpLocation);
        }

        if ($cert3_transactions) {
            $tmpLocation = '/tmp/' . uniqid("cert3_") . ".tmp.csv";
            $fp = fopen($tmpLocation, 'w');
            $headers = array('Id sender', 'sender type', 'fecha', 'transacción id', 'id receiver', 'receiver type',
                'Concepto', 'servicio', 'cantidad R', 'tx_type');
            fputcsv($fp, $headers);

            foreach ($cert3_transactions as $transaction) {
                fputcsv($fp, $transaction);
            }
            fputcsv($fp, array('Total company accounts:', sizeof($company_c3_accounts)));
            fputcsv($fp, array('Total private accounts:', sizeof($private_c3_accounts)));
            fputcsv($fp, array('Total REC ammount:', $total_c3_amount));
            fputcsv($fp, array('Total transactions:', sizeof($cert3_transactions)));
            fclose($fp);

            $this->scheduleMailing($ltab_account, $em, $tmpLocation);
        }

        return new Response(
            "No content",
            204,
            []
        );
    }
}