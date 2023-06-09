<?php

namespace App\Controller\CRUD;

use App\Controller\Transactions\IncomingController2;
use App\DataFixture\ActivityFixture;
use App\Entity\Activity;
use App\Entity\Badge;
use App\Entity\Campaign;
use App\Entity\DelegatedChangeData;
use App\Entity\Mailing;
use App\Entity\MailingDelivery;
use App\Entity\SmsTemplates;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Exception\AppException;
use App\Financial\Driver\LemonWayInterface;
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
use App\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Offer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Stubs\DocumentManager;
use App\Document\Transaction;
use App\Controller\Management\Admin\UsersController;

/**
 * Class AccountsController
 * @package App\Controller\CRUD
 */
class AccountsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_SHOW] = self::ROLE_PUBLIC;
        $grants[self::CRUD_SEARCH] = self::ROLE_PUBLIC;
        $grants[self::CRUD_UPDATE] = self::ROLE_USER;
        return $grants;
    }

    public function showAction($role, $id)
    {
        return parent::showAction($role, $id);
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


        $only_with_offers = $query->only_with_offers ?? 0;

        if($only_with_offers === '1') {

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
     * @param $role
     * @return Response
     */
    public function Search4Action(Request $request, $role)
    {
//        $limit = $request->query->getInt('limit', 10);
//        $offset = $request->query->getInt('offset', 0);
//        $sort = $request->query->get('sort', 'id');
//        $order = $request->query->getAlpha('order', 'DESC');

        $campaign = $request->query->get('campaigns');
        $campaign_code = $request->query->get('campaign_code');
        $search = $request->query->get('search');

        $activity_id = $request->query->get('activity_id');
        $account_subtype = strtoupper($request->query->get('subtype', ''));
        $only_with_offers = $request->query->get('only_with_offers', false);
        $rect_box = $request->query->get('rect_box', [-90.0, -90.0, 90.0, 90.0]);
        $badge_id = $request->query->get('badge_id', '');

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
            'c.cat',
            'c.esp'
        ];
        if ($only_with_offers === '1' || $only_with_offers === 'true') {
           $searchFields[] = 'o.description';
        }
        $like = $qb->expr()->orX();
        foreach ($searchFields as $field) {
            $like->add($qb->expr()->like($field, $qb->expr()->literal('%' . $search . '%')));
        }
        $and->add($like);
        $and->add($qb->expr()->eq('a.on_map', 1));
        $and->add($qb->expr()->eq('a.active', 1));
        //geo query
        $and->add($qb->expr()->gt('a.latitude', $rect_box[0]));
        $and->add($qb->expr()->lt('a.latitude', $rect_box[2]));
        $and->add($qb->expr()->gt('a.longitude', $rect_box[1]));
        $and->add($qb->expr()->lt('a.longitude', $rect_box[3]));

        $and->add($qb->expr()->eq('a.type', $qb->expr()->literal('COMPANY')));
        //$and->add($qb->expr()->gt('o.end', date_format(new \DateTime('NOW'), 'Y-m-d')));

        if (isset($campaign)) $and->add($qb->expr()->eq('cp.id', $campaign));
        if (isset($campaign_code)) $and->add($qb->expr()->eq('cp.code', $qb->expr()->literal($campaign_code)));

        if ($account_subtype != '') $and->add($qb->expr()->like('a.subtype', $qb->expr()->literal($account_subtype)));

        $select = 'a.id, ' .
            'a.name, ' .
            'a.company_image, ' .
            'a.latitude, ' .
            'a.longitude, ' .
            'a.description, ' .
            'a.public_image, ' .
            'tr.code as tier, ' .
            'identity(a.activity_main) as activity, ' .
            'a AS account, ' .
            'o.active AS offer, ' .
            'SUM(o.active) AS totalOffers, ' .
            'o.end, ' .
            'cp.name AS campaign'; // TODO Remove it when version higher than 2.1.0

        $qb = $qb
            ->distinct()
            ->from(Group::class, 'a')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.campaigns', 'cp')
            ->leftJoin('a.offers', 'o')
            ->leftJoin('a.level', 'tr');

        if($badge_id != ''){
            $qb = $qb->leftJoin('a.badges', 'b')->setParameter('badge_id', $badge_id);
            $select = $select.', b.name AS badge';
            $and->add('b.id = :badge_id');
        }

        $qb = $qb->where($and)
                 ->andWhere('tr.code LIKE :kyc')
                 ->orWhere('tr.code LIKE :kyc2')
                 ->setParameter('kyc', 'KYC2')
                 ->setParameter('kyc2', 'KYC3');

        $now = new \DateTime('NOW');

        if ($only_with_offers === '1' || $only_with_offers === 'true') {
            $_and = $qb->expr()->andX();
            $_and->add($qb->expr()->eq('o2.company', 'a.id'));
            $_and->add($qb->expr()->eq('o2.active', 1));

            $qb->setParameter('now', $now);
            $_and->add('o2.end > :now');

            $qbAux = $em->createQueryBuilder()
                ->select('count(o2)')
                ->from(Offer::class, 'o2')
                ->where($_and);

            $and->add($qb->expr()->gt("(" . $qbAux->getDQL() . ")", $qb->expr()->literal(0)));
        }

        $query_resp = $qb
            ->select($select)
            ->groupBy('a.id')
            ->orderBy('o.active', 'DESC')
            ->getQuery()
            ->getResult();

        $hasActivity = isset($activity_id) and is_numeric($activity_id);
        $isAdmin = $role == 'admin';

        $activities_id = [];
        $same_activity_elements = [];

        if($hasActivity and !$isAdmin) {
            $a_qb = $em->createQueryBuilder();
            $a_qb = $a_qb
                ->from(Activity::class, 'p')
                ->leftJoin(Activity::class, 'a', 'WITH', 'a.parent = p.id')
                ->where('p.id =' . $activity_id);

            $activities = $a_qb
                ->select('p, a')
                ->getQuery()
                ->getResult();

            for ($i = 0; $i < sizeof($activities); $i++) {
                if(isset($activities[$i])){
                    $activities_id[] = $activities[$i]->getId();
                }
            }
        }

        $elements = $this->secureOutput($query_resp);
        for ($i = 0; $i < sizeof($elements); $i++) {
            // TODO Remove it when version higher than 2.1.0
            $elements[$i]['in_ltab_campaign'] = array_key_exists("campaign", $elements[$i]) &&
                $elements[$i]["campaign"] == Campaign::BONISSIM_CAMPAIGN_NAME;
            unset($elements[$i]['campaign']);
            $elements[$i]['has_offers'] = (array_key_exists("totalOffers", $elements[$i]) and $elements[$i]['totalOffers'] > 0 );
            unset($elements[$i]['end']);

            $account_campaigns = $elements[$i]["account"]["campaigns"];
            $campaigns_id_list = [];
            if(count($account_campaigns) > 0){
                $totalCampaigns = count($account_campaigns);
                for ($ii = 0; $ii < $totalCampaigns; $ii++) {
                    $campaigns_id_list[] = ['id' => $account_campaigns[$ii]["id"], 'code' => $account_campaigns[$ii]['code']];
                }
            }
            $elements[$i]['campaigns'] = $campaigns_id_list;
            unset($elements[$i]['account']);

            $is_commerce_verd = false;
            //TODO I think we could do the same here with is_commerce_verd
            //but I did not that because now is working
            $elements[$i]["is_cultural"] = $query_resp[$i]["account"]->isCultural();
            foreach ($query_resp[$i]["account"]->getActivities() as $account_activity){
                if($account_activity->getName() === Activity::GREEN_COMMERCE_ACTIVITY){
                    $is_commerce_verd = true;
                }

                if(in_array($account_activity->getId(), $activities_id)){
                    $elements[$i]["is_commerce_verd"] = $is_commerce_verd;
                    //$elements[$i]["is_cultural"] = $is_cultural;
                    $same_activity_elements[] = $elements[$i];
                }
            }
            $elements[$i]["is_commerce_verd"] = $is_commerce_verd;
        }
        if(($hasActivity and !$isAdmin)){
            $elements = $same_activity_elements;
        }

        return $this->rest(
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
        $resp = parent::updateAction($request, $role, $id);
        if($resp->getStatusCode()==200 && $request->request->has('rezero_b2b_access') &&
            $request->request->get('rezero_b2b_access')===Group::ACCESS_STATE_GRANTED){
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            /** @var Group $account */
            $account = $em->getRepository(Group::class)->find($id);
            $template = $em->getRepository(SmsTemplates::class)->findOneBy(['type' => 'rezero_b2b_access_granted']);
            $prefix = $account->getKycManager()->getPrefix();
            $phone = $account->getKycManager()->getPhone();

            UsersController::sendSMSv4($prefix, $phone, $template->getBody(), $this->container);
        }
        return $resp;
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

    private function userCanAccessAccount(User $user, Group $account){

        /** @var UserGroup $permission */
        foreach ($user->getUserGroups() as $permission){
            if($permission->getGroup()->getId() == $account->getId()){
                return true;
            }
        }
        return false;
    }



    /**
     * @param EngineInterface $templating
     * @param Group $account
     * @param string
     * @return string
     */
    public function generateClientsAndProvidersReportHtml(EngineInterface $templating, Group $account, $type = 'pdf'){

        return $templating->render(
            'Pdf/product_clients_and_providers.html.twig',
            ['account' => $account,
             'language'=> $account,
             'type'=> $type]
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

        /** @var User $user */
        $user = $this->getUser();
        if ($role != 'admin' && !$this->userCanAccessAccount($user, $account))
            throw new HttpException(403, "No permissions to read account.");

        $format = $request->headers->get('Accept');
        if($format == 'text/html') {
            return new Response(
                $this->generateClientsAndProvidersReportHtml($templating, $account, 'html'),
                200,
                ['Content-Type' => 'text/html']
            );
        }
        elseif ($format == 'application/pdf'){
            return new Response(
                $this->generateClientsAndProvidersReportPdf($templating, $account, 'pdf'),
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
     * @param EngineInterface $templating
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     */
    public function emailReportClientsAndProvidersAction(EngineInterface $templating, Request $request, $role, $id){
        $this->checkPermissions($role, self::CRUD_SHOW);

        /** @var Group $account */
        $account = $this->findObject($id);

        /** @var User $user */
        $user = $this->getUser();
        if ($role != 'admin' && !$this->userCanAccessAccount($user, $account))
            throw new HttpException(403, "No permissions to read account.");

        $em = $this->getDoctrine()->getManager();

        $mailing = new Mailing();
        $mailing->setStatus(Mailing::STATUS_CREATED);
        $mailing->setSubject("B2B Products Report");
        $mailing->setContent('Find B2B report attached.');
        $mailing->setScheduledAt(new \DateTime());
        $mailing->setAttachments(['b2b_report.pdf' => 'b2b_report']);

        $delivery = new MailingDelivery();
        $delivery->setStatus(MailingDelivery::STATUS_CREATED);
        $delivery->setAccount($account);
        $delivery->setMailing($mailing);
        $em->persist($mailing);
        $mailing->setStatus(Mailing::STATUS_SCHEDULED);
        $em->persist($mailing);
        $em->persist($delivery);
        $em->flush();

        return $this->rest(
            200,
            "ok",
            "Report sent successfully"
        );
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

        /** @var IncomingController3 $tc */
        $tc = $this->get('app.incoming_controller3');

        $repo = $this->getDoctrine()->getRepository(Group::class);
        /** @var Group $receiver */
        $receiver = $repo->find($this->getParameter('id_group_root'));
        /** @var Group $sender */
        $sender = $repo->find($accountId);
        if(!$sender) throw new AppException(404, "Invalid account_id: not found");

        $request->request->set('sender', $sender->getId());
        $request->request->set('receiver', $receiver->getId());
        $request->request->set('sec_code', $otp);

        $crypto_currency = $this->getParameter('crypto_currency');
        /** @var Response $resp */
        $resp =  $tc->adminThirdTransaction($request, strtolower($crypto_currency));

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

                $crypto_currency = $this->getParameter('crypto_currency');
                /** @var Response $resp */
                $resp =  $tc->adminThirdTransaction($request, strtolower($crypto_currency));

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
        return $this->rest(
            static::HTTP_STATUS_CODE_CREATED,
            "ok",
            "Created successfully",
            $output
        );
    }

    /**
     * @param $user_account
     * @param EntityManagerInterface $em
     */
    protected function scheduleMailing($account, EntityManagerInterface $em, $filename): void
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
