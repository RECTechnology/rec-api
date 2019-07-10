<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\Category;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\Offer;
use Telepay\FinancialApiBundle\Entity\User;

class MapController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }

    /**
     * @param Request $request, int $id
     * @return Response
     */
    public function setVisibility(Request $request, $aount_id){
        if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        //$id = $request->get('id');
        $on_map = $request->get('on_map');
        $group = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'id' => $aount_id
        ));
        if(!$group){
            throw new HttpException(400, 'Incorrect ID');
        }
        $group->setOn_map($on_map);
        $em->persist($group);
        try{
            $em->flush();
            return $this->rest(
                200,
                "Visibility changed successfully"
            );
        } catch(DBALException $ex){
            throw new HttpException(409, $ex->getMessage());
        }
    }

}