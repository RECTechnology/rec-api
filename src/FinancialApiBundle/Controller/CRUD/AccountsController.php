<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
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
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

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

        $qb = $qb
            ->distinct()
            ->from(Group::class, 'a')
            ->leftJoin('a.offers', 'o')
            ->leftJoin('a.category', 'c')
            ->where($and);

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
            $lwResp = $lw->callService(
                'MoneyOut',
                [
                    'wallet' => $sender->getCif(),
                    'amountTot' => $amount,
                    'message' => "MoneyOut from account {$sender->getName()}",
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
        $output = $this->securizeOutput($entity);
        return $this->restV2(
            static::HTTP_STATUS_CODE_CREATED,
            "ok",
            "Created successfully",
            $output
        );
    }
}
