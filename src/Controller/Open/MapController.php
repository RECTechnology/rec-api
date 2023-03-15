<?php

namespace App\Controller\Open;

use App\Entity\Activity;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use DoctrineExtensions\Query\Mysql\Exp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Group;
use App\Entity\Offer;

class MapController extends BaseApiController{

    function getRepositoryName()
    {
        return "FinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function SearchAction(Request $request)
    {
//        $limit = $request->query->getInt('limit', 10);
//        $offset = $request->query->getInt('offset', 0);
//        $sort = $request->query->get('sort', 'id');
//        $order = $request->query->getAlpha('order', 'DESC');
        $campaign = $request->query->get('campaigns');
        $campaign_code = $request->query->get('campaign_code');
        $search = $request->query->get('search');
        $badge_id = $request->query->get('badge_id', null);
        $account_subtype = strtoupper($request->query->get('subtype', ''));
        $only_with_offers = $request->query->get('only_with_offers', 0);
        $rect_box = $request->query->get('rect_box', [-90.0, -90.0, 90.0, 90.0]);
        $activity_id = $request->query->get('activity_id');
        $is_commerce_verd = $request->query->get('is_commerce_verd', false);
        $hasActivity = isset($activity_id) and is_numeric($activity_id);

        if (!in_array($account_subtype, [Group::ACCOUNT_SUBTYPE_RETAILER, Group::ACCOUNT_SUBTYPE_WHOLESALE, ""])) {
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
        $and->add($qb->expr()->eq('a.active', 1));
        //geo query
        $and->add($qb->expr()->gt('a.latitude', $rect_box[0]));
        $and->add($qb->expr()->lt('a.latitude', $rect_box[2]));
        $and->add($qb->expr()->gt('a.longitude', $rect_box[1]));
        $and->add($qb->expr()->lt('a.longitude', $rect_box[3]));

        $and->add($qb->expr()->eq('a.type', $qb->expr()->literal(Group::ACCOUNT_TYPE_ORGANIZATION)));

        if (isset($campaign)) $and->add($qb->expr()->eq('cp.id', $campaign));
        if (isset($campaign_code)) $and->add($qb->expr()->eq('cp.code', $qb->expr()->literal($campaign_code)));

        if ($account_subtype != '') $and->add($qb->expr()->like('a.subtype', $qb->expr()->literal($account_subtype)));

        if ($only_with_offers == 1) {
            $_and = $qb->expr()->andX();
            $_and->add($qb->expr()->eq('o2.company', 'a.id'));
            $_and->add($qb->expr()->eq('o2.active', 1));
            $qbAux = $em->createQueryBuilder()
                ->select('count(o2)')
                ->from(Offer::class, 'o2')
                ->where($_and);

            $and->add($qb->expr()->gt("(" . $qbAux->getDQL() . ")", $qb->expr()->literal(0)));
        }
        if($hasActivity) {
            //TODO no coge los parents, el de user si
            $a_qb = $em->createQueryBuilder();
            $activities = $a_qb
                ->select('ac')
                ->from(Activity::class, 'ac')
                ->where('ac.id =' . $activity_id. ' OR ac.parent = '.$activity_id)
                ->getQuery()
                ->getResult();
            $activities_ids = [];
            foreach($activities as $activity){
                $activities_ids[] = $activity->getId();
            }
            //$and->add($qb->expr()->in('a.activity_main', $activities_ids));
        }

        $qb = $qb
            ->distinct()
            ->from(Group::class, 'a')
            ->leftJoin('a.offers', 'o')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.campaigns', 'cp')
            ->where($and);

        if($badge_id){
            $qb->innerJoin('a.badges', 'bg', Join::WITH, 'bg.id = :badge_id')->setParameter('badge_id', $badge_id);
        }

        //this filter is not used anywhere, future feature
        if($is_commerce_verd === '1' || $is_commerce_verd === 'true'){
            $greenCommerceActivity = $em->getRepository(Activity::class)->findOneBy(array(
                'name' => Activity::GREEN_COMMERCE_ACTIVITY
            ));

            if($greenCommerceActivity){
                $qb->innerJoin('a.activities', 'ag', Join::WITH, 'ag.id = :commerce_id')->setParameter('commerce_id', $greenCommerceActivity->getId());
            }
        }

        $select = 'a.id, ' .
            'a.name, ' .
            'a.company_image, ' .
            'a.latitude, ' .
            'a.longitude, ' .
            'a.country, ' .
            'a.city, ' .
            'a.zip, ' .
            'a.street, ' .
            'a.street_type, ' .
            'a.address_number, ' .
            'a.prefix, ' .
            'a.phone, ' .
            'a.type, ' .
            'a.subtype, ' .
            'a.description, ' .
            'a.schedule, ' .
            'a.public_image, ' .
            'a.web, ' .
            'a.offered_products, ' .
            'a.needed_products, '.
            'identity(a.activity_main) as activity, ' .
            'cp.code AS campaign';

        $elements = $qb
            ->select($select)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();


        $elements = $this->secureOutput($elements);

        $iMax = count($elements);
        $filtered_elements = [];
        for($i = 0; $i < $iMax; $i++){
            $offersInGroup = $em
                ->createQuery('SELECT o FROM '.Offer::class.' o WHERE o.company = :companyid AND o.active = :active')
                ->setParameters(array(
                    'companyid' => $elements[$i]["id"],
                    'active' => 1))
                ->getResult();

            $elements[$i]["offers"] = $offersInGroup;
            /** @var Group $account */
            $account = $em->getRepository(Group::class)->find($elements[$i]["id"]);
            $elements[$i]['is_commerce_verd'] = $account->isGreenCommerce();
            $elements[$i]['is_cultural'] = $account->isCultural();

            if($hasActivity){
                $current_account = $em->getRepository(Group::class)->find($elements[$i]['id']);
                foreach ($current_account->getActivities() as $account_activity){
                    if(in_array($account_activity->getId(), $activities_ids)){
                        $filtered_elements[] = $elements[$i];
                    }
                }

            }else{
                $filtered_elements[] = $elements[$i];
            }

        }


        return $this->rest(
            200,
            "ok",
            "Request successful",
            array(
                'total' => count($filtered_elements),
                'elements' => $filtered_elements
            )
        );
    }
}
