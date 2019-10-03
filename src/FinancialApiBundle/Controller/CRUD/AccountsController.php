<?php

namespace App\FinancialApiBundle\Controller\CRUD;

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

        $html = $templating->render(
            'FinancialApiBundle:Pdf:product_clients_and_providers.html.twig',
            ['account' => $account]
        );
        $format = $request->headers->get('Accept');
        if($format == 'text/html') {
            return new Response(
                $html,
                200,
                ['Content-Type' => 'text/html']
            );
        }
        elseif ($format == 'application/pdf'){

            $pdf = new Html2Pdf();
            $pdf->writeHTML($html);

            try {
                return new Response(
                    $pdf->output(null, 'S'),
                    200,
                    [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE
                    ]
                );
            } catch (Html2PdfException $e) {
                throw new HttpException(400, "Invalid pdf requested: ".  $e->getMessage(), $e);
            }
        }
        throw new HttpException(400, "Invalid accept format " . $request->headers->get('Accept'));
    }
}
