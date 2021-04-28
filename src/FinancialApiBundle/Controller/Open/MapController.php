<?php

namespace App\FinancialApiBundle\Controller\Open;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Offer;

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
            'cp.name AS campaign';

        $elements = $qb
            ->select($select)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();


        $elements = $this->secureOutput($elements);

        $now = new \DateTime();
        for($i = 0; $i < count($elements); $i++){
            $offersInGroup = $em
                ->createQuery('SELECT o FROM '.Offer::class.' o WHERE o.company = :companyid AND o.end < :today')
                ->setParameters(array(
                    'companyid' => $elements[$i]["id"],
                    'today' => $now))
                ->getResult();

            $elements[$i]["offers"] = $offersInGroup;
        }


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
}