<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\Offer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Stubs\DocumentManager;
use App\FinancialApiBundle\Document\Transaction;

/**
 * Class AccountsController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class AccountsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_SEARCH] = self::ROLE_PUBLIC;
        $grants[self::CRUD_UPDATE] = self::ROLE_USER;
        return $grants;
    }

    /**
     * @param Request $request
     * @return array
     * @throws NonUniqueResultException
     */
    public function search(Request $request){
        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);
        $query = json_decode($request->query->get('query', '{}'));
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->getAlpha('order', 'DESC');

        $rect_box = isset($query->rect_box)?$query->rect_box: [-90.0, -90.0, 90.0, 90.0];
        $search = isset($query->search)?$query->search: '';

        $account_subtype = isset($query->subtype)? strtoupper($query->subtype): '';

        if(!in_array($account_subtype, ["RETAILER", "WHOLESALE", ""])){
            throw new HttpException(400, "Invalid subtype '$account_subtype', valid options: 'retailer', 'wholesale'");
        }

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $and = $qb->expr()->andX();
        $searchFields = [
            'a.id',
            'a.name',
            'a.phone',
            'a.cif',
            'a.city',
            'a.street',
            'a.description',
            'o.discount',
            'o.description',
            'c.cat',
            'c.esp',
            'c.eng'
        ];
        $like = $qb->expr()->orX();
        foreach ($searchFields as $field) {
            $like->add($qb->expr()->like($field, $qb->expr()->literal('%' . $search . '%')));
        }
        $and->add($like);
        $and->add($qb->expr()->eq('a.on_map', 1));
        //geo query
        $and->add($qb->expr()->gt('a.latitude', $rect_box[0]));
        $and->add($qb->expr()->lt('a.latitude', $rect_box[2]));
        $and->add($qb->expr()->gt('a.longitude', $rect_box[1]));
        $and->add($qb->expr()->lt('a.longitude', $rect_box[3]));

        $and->add($qb->expr()->eq('a.type', $qb->expr()->literal('COMPANY')));

        $campaign = $request->query->get('campaigns');
        if(isset($campaign)){
            $and->add($qb->expr()->eq('cp.id', $campaign));
        }

        if($account_subtype != '')
            $and->add($qb->expr()->like('a.subtype', $qb->expr()->literal($account_subtype)));


        $only_with_offers = isset($query->only_with_offers)? $query->only_with_offers: 0;

        if($only_with_offers == 1) {

            $qbAux = $em->createQueryBuilder()
                ->select('count(o2)')
                ->from(Offer::class, 'o2')
                ->where($qb->expr()->eq('o2.company', 'a.id'));
            $and->add($qb->expr()->gt("(" . $qbAux->getDQL() . ")", $qb->expr()->literal(0)));
        }

        if(isset($campaign)){
            $qb = $qb
                ->distinct()
                ->from(Group::class, 'a')
                ->leftJoin('a.offers', 'o')
                ->leftJoin('a.category', 'c')
                ->Join('a.campaigns', 'cp')
                ->where($and);
        }else {
            $qb = $qb
                ->distinct()
                ->from(Group::class, 'a')
                ->leftJoin('a.offers', 'o')
                ->leftJoin('a.category', 'c')
                ->where($and);
            }


        $total = $qb
            ->select('count(distinct(a))')
            ->getQuery()
            ->getSingleScalarResult();

        $qbAux2 = $em->createQueryBuilder()
            ->select('count(o3)')
            ->from(Offer::class, 'o3')
            ->where($qb->expr()->eq('o3.company', 'a.id'));

        $elements = $qb
            ->select('a')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy($sort == 'offer_count'? $sort: 'a.' . $sort, $order)
            ->getQuery()
            ->getResult();

        return [intval($total), $elements];
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function Search4Action(Request $request)
    {
//        $limit = $request->query->getInt('limit', 10);
//        $offset = $request->query->getInt('offset', 0);
//        $sort = $request->query->get('sort', 'id');
//        $order = $request->query->getAlpha('order', 'DESC');

        $campaign = $request->query->get('campaigns');
        $search = $request->query->get('search');
        $account_subtype = strtoupper($request->query->get('subtype', ''));
        $only_with_offers = $request->query->get('only_with_offers', 0);
        $rect_box = $request->query->get('rect_box', [-90.0, -90.0, 90.0, 90.0]);

        if (!in_array($account_subtype, ["RETAILER", "WHOLESALE", ""])) {
            throw new HttpException(400, "Invalid subtype '$account_subtype', valid options: 'retailer', 'wholesale'");
        }

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $and = $qb->expr()->andX();
        $searchFields = [
            'a.name',
            'a.description',
            'o.description',
            'c.cat',
            'c.esp'
        ];
        $like = $qb->expr()->orX();
        foreach ($searchFields as $field) {
            $like->add($qb->expr()->like($field, $qb->expr()->literal('%' . $search . '%')));
        }
        $and->add($like);
        $and->add($qb->expr()->eq('a.on_map', 1));
        //geo query
        $and->add($qb->expr()->gt('a.latitude', $rect_box[0]));
        $and->add($qb->expr()->lt('a.latitude', $rect_box[2]));
        $and->add($qb->expr()->gt('a.longitude', $rect_box[1]));
        $and->add($qb->expr()->lt('a.longitude', $rect_box[3]));

        $and->add($qb->expr()->eq('a.type', $qb->expr()->literal('COMPANY')));

        if (isset($campaign)) $and->add($qb->expr()->eq('cp.id', $campaign));

        if ($account_subtype != '') $and->add($qb->expr()->like('a.subtype', $qb->expr()->literal($account_subtype)));

        if ($only_with_offers == 1) {
            $qbAux = $em->createQueryBuilder()
                ->select('count(o2)')
                ->from(Offer::class, 'o2')
                ->where($qb->expr()->eq('o2.company', 'a.id'));
            $and->add($qb->expr()->gt("(" . $qbAux->getDQL() . ")", $qb->expr()->literal(0)));
        }

        $qb = $qb
            ->distinct()
            ->from(Group::class, 'a')
            ->leftJoin('a.offers', 'o')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.campaigns', 'cp')
            ->where($and);

        $select = 'a.id, ' .
            'a.name, ' .
            'a.company_image, ' .
            'a.latitude, ' .
            'a.longitude, ' .
            'a.description, ' .
            'a.public_image, ' .
            'cp.name AS campaign';

        $elements = $qb
            ->select($select)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();


        $elements = $this->secureOutput($elements);
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => sizeof($elements),
                'elements' => $elements
            )
        );
    }

    /**
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     * @throws AnnotationException
     */
    public function updateAction(Request $request, $role, $id)
    {
        if(self::ROLE_PATH_MAPPINGS[$role] == self::ROLE_USER) {
            /** @var Group $account */
            $account = $this->findObject($id);
            /** @var User $user */
            $user = $this->getUser();
            if($this->userCanUpdateAccount($user, $account))
                return parent::updateAction($request, $role, $id);
            throw new HttpException(403, "Insufficient permissions for account");
        }
        return parent::updateAction($request, $role, $id);
    }


    public function addRelationshipAction(Request $request, $role, $id, $relationship)
    {
        if(self::ROLE_PATH_MAPPINGS[$role] == self::ROLE_USER) {
            /** @var Group $account */
            $account = $this->findObject($id);
            /** @var User $user */
            $user = $this->getUser();
            if($this->userCanUpdateAccount($user, $account))
                return parent::addRelationshipAction($request, $role, $id, $relationship);
            throw new HttpException(403, "Insufficient permissions for account");
        }
        return parent::addRelationshipAction($request, $role, $id, $relationship);
    }

    public function deleteRelationshipAction(Request $request, $role, $id1, $relationship, $id2)
    {
        if(self::ROLE_PATH_MAPPINGS[$role] == self::ROLE_USER) {
            /** @var Group $account */
            $account = $this->findObject($id1);
            /** @var User $user */
            $user = $this->getUser();
            if($this->userCanUpdateAccount($user, $account))
                return parent::deleteRelationshipAction($request, $role, $id1, $relationship, $id2);
            throw new HttpException(403, "Insufficient permissions for account");
        }
        return parent::deleteRelationshipAction($request, $role, $id1, $relationship, $id2);
    }

    public function indexRelationshipAction(Request $request, $role, $id, $relationship)
    {
        if(self::ROLE_PATH_MAPPINGS[$role] == self::ROLE_USER) {
            /** @var Group $account */
            $account = $this->findObject($id);
            /** @var User $user */
            $user = $this->getUser();
            if($this->userCanUpdateAccount($user, $account))
                return parent::indexRelationshipAction($request, $role, $id, $relationship);
            throw new HttpException(403, "Insufficient permissions for account");
        }
        return parent::indexRelationshipAction($request, $role, $id, $relationship);
    }

    private function userCanUpdateAccount(User $user, Group $account){
        /** @var UserGroup $permission */
        foreach ($user->getUserGroups() as $permission){
            if($permission->getGroup()->getId() == $account->getId()){
                if(in_array('ROLE_ADMIN', $permission->getRoles()))
                    return true;
                else
                    return false;
            }
        }
        return false;
    }


    /**
     * @param EngineInterface $templating
     * @param Group $account
     * @return string
     */
    public function generateClientsAndProvidersReportHtml(EngineInterface $templating, Group $account){
        return $templating->render(
            'FinancialApiBundle:Pdf:product_clients_and_providers.html.twig',
            ['account' => $account]
        );
    }

    /**
     * @param EngineInterface $templating
     * @param Group $account
     * @return string
     */
    public function generateClientsAndProvidersReportPdf(EngineInterface $templating, Group $account){
        return $this->get('knp_snappy.pdf')->getOutputFromHtml(
            $this->generateClientsAndProvidersReportHtml($templating, $account)
        );
    }

    /**
     * @param EngineInterface $templating
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     */
    public function reportClientsAndProvidersAction(EngineInterface $templating, Request $request, $role, $id){
        $this->checkPermissions($role, self::CRUD_SHOW);

        /** @var Group $account */
        $account = $this->findObject($id);

        $format = $request->headers->get('Accept');
        if($format == 'text/html') {
            return new Response(
                $this->generateClientsAndProvidersReportHtml($templating, $account),
                200,
                ['Content-Type' => 'text/html']
            );
        }
        elseif ($format == 'application/pdf'){
            return new Response(
                $this->generateClientsAndProvidersReportPdf($templating, $account),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE
                ]
            );
        }
        throw new HttpException(400, "Invalid accept format " . $request->headers->get('Accept'));
    }

    /**
     * @param Request $request
     * @param $accountId
     * @return array
     */
    public function withdrawal(Request $request, $accountId){

        $otp = $request->request->get('otp', 0);
        $request->request->remove('otp');

        $currency = $request->request->get('currency', "");
        if(strtoupper($currency) != 'EUR')
            throw new AppException(400, "Param 'currency' is required to be 'EUR' for withdrawals");

        $eurAmount = $request->request->get('amount', 0);
        $request->request->set('amount', $eurAmount * 1e6);

        /** @var IncomingController2 $tc */
        $tc = $this->get('app.incoming_controller');

        $repo = $this->getDoctrine()->getRepository(Group::class);
        /** @var Group $receiver */
        $receiver = $repo->find($this->getParameter('id_group_root'));
        /** @var Group $sender */
        $sender = $repo->find($accountId);
        if(!$sender) throw new AppException(404, "Invalid account_id: not found");

        $request->request->set('sender', $sender->getId());
        $request->request->set('receiver', $receiver->getId());
        $request->request->set('sec_code', $otp);

        /** @var Response $resp */
        $resp =  $tc->adminThirdTransaction($request, 'rec');

        $result = json_decode($resp->getContent());
        if($result->status == 'success'){
            /** @var LemonWayInterface $lw */
            $lw = $this->get('net.app.driver.lemonway.eur');

            $amount = sprintf("%.2f", $eurAmount / 1e2);
            $concept = $request->request->get('concept', "MoneyOut from account {$sender->getName()}");
            $lwResp = $lw->callService(
                'MoneyOut',
                [
                    'wallet' => $sender->getCif(),
                    'amountTot' => $amount,
                    'message' => $concept,
                    'autoComission' => 0
                ]
            );
            if(is_array($lwResp)){

                $request->request->set('sender', $receiver);
                $request->request->set('receiver', $sender);

                /** @var Response $resp */
                $resp =  $tc->adminThirdTransaction($request, 'rec');

                $result = json_decode($resp->getContent());
                if($result->status != 'success'){
                    throw new AppException(500, "FATAL: Withdrawal rollback failed: {$result->message}");
                }
                throw new AppException(503, "Provider error", [$lwResp]);
            }
            return ['tx' => $result, 'lemonway' => $lwResp];
        }

        throw new AppException($resp->getStatusCode(), "Withdrawal failed: {$result->message}");
    }

    /**
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     */
    public function createWithdrawalAction(Request $request, $role, $id)
    {
        $this->checkPermissions($role, self::CRUD_CREATE);
        $entity = $this->withdrawal($request, $id);
        $output = $this->secureOutput($entity);
        return $this->restV2(
            static::HTTP_STATUS_CODE_CREATED,
            "ok",
            "Created successfully",
            $output
        );
    }
    /**
     * @param EngineInterface $templating
     * @param Request $request
     * @return Response
     * @param $role
     */
    public function reportLTABAction(EngineInterface $templating, Request $request, $role){

        $this->checkPermissions($role, self::CRUD_CREATE);

        /** @var DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManager();
        $repoGroup = $em->getRepository(Group::class);

        $_since = $request->request->get("since", "0");
        $_to = $request->request->get("to", "0");

        $campaign = $em->getRepository(Campaign::class)->findOneBy(["name" => Campaign::BONISSIM_CAMPAIGN_NAME]);

        if($_since!="0"){
            $since = new \MongoDate(strtotime($_since .' 00:00:00'));
        }
        else{
            $since = new \MongoDate($campaign->getInitDate()->getTimestamp());
        }

        if($_to!="0"){
            $to = new \MongoDate(strtotime($_to .' 23:59:59'));
        }
        else{
            $to = new \MongoDate($campaign->getEndDate()->getTimestamp());
        }

        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
            ->field('method')->equals('rec')
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
                $transaction->getInternal()){
                if($sender->getType() == Group::ACCOUNT_TYPE_ORGANIZATION and sizeof($sender->getCampaigns())){
                    $receiver_c2 = $repoGroup->findOneBy(['rec_address' => $transaction->getPayOutInfo()['address']]);
                    //search cert2 transactions
                    if($receiver_c2 and $receiver_c2->getType() == Group::ACCOUNT_TYPE_PRIVATE and sizeof($receiver_c2->getCampaigns())){
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
                            if(round(($trans->getAmount() / 100) * Campaign::PERCENTAGE , 2) ==
                                round($transaction->getAmount(), 2)){
                                $receiver_c1 = $repoGroup->findOneBy(['rec_address' => $trans->getPayOutInfo()['address']]);
                                if($receiver_c1->getId() == $sender->getId()){
                                    $sender_c1 = $repoGroup->find($trans->getGroup());
                                    if($sender_c1->getKycManager()->getId() == $receiver_c2->getKycManager()->getId()){
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
            if($sender->getType() == Group::ACCOUNT_TYPE_PRIVATE and sizeof($sender->getCampaigns())){
                $receiver_c3 = $repoGroup->findOneBy(['rec_address' => $transaction->getPayOutInfo()['address']]);
                if($receiver_c3 and $receiver_c3->getType() == Group::ACCOUNT_TYPE_PRIVATE){
                    $tx_type = 'Transfer';
                }else{
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

        if($cert2_transactions){
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

        if($cert1_transactions){
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

        if($cert3_transactions){
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

    /**
     * @param $user_account
     * @param EntityManagerInterface $em
     */
    private function scheduleMailing($account, EntityManagerInterface $em, $filename): void
    {
        $mailing = new Mailing();
        $mailing->setStatus(Mailing::STATUS_CREATED);
        $mailing->setSubject($filename);
        $mailing->setContent($filename.' report');
        $mailing->setScheduledAt(new \DateTime());
        $mailing->setAttachments([$filename => file_get_contents($filename)]);

        $delivery = new MailingDelivery();
        $delivery->setStatus(MailingDelivery::STATUS_CREATED);
        $delivery->setAccount($account);
        $delivery->setMailing($mailing);
        $em->persist($mailing);
        $mailing->setStatus(Mailing::STATUS_SCHEDULED);
        $em->persist($mailing);
        $em->persist($delivery);
        $em->flush();
    }
}
