<?php

namespace App\Controller\CRUD;

use App\Entity\Group;
use App\Entity\ProductKind;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProductKindsController
 * @package App\Controller\CRUD
 */
class ProductKindsController extends CRUDController {

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        $grants = parent::getCRUDGrants();
        $grants[self::CRUD_SEARCH] = self::ROLE_USER;
        $grants[self::CRUD_INDEX] = self::ROLE_USER;
        $grants[self::CRUD_SHOW] = self::ROLE_USER;
        $grants[self::CRUD_CREATE] = self::ROLE_USER;
        return $grants;
    }

    public function searchAction(Request $request, $role)
    {
        if($request->query->has('search')){
            $search = $request->query->get('search');
            if(!$search) throw new HttpException(403, 'Nothing to search');
            $em = $this->getEntityManager();
            $qb = $em->createQueryBuilder();

            $products = $qb->select('p')
                ->from(ProductKind::class,'p')
                ->where($qb->expr()->orX(
                    $qb->expr()->like('p.name', $qb->expr()->literal('%'.$search.'%')),
                    $qb->expr()->like('p.name_es', $qb->expr()->literal('%'.$search.'%')),
                    $qb->expr()->like('p.name_ca', $qb->expr()->literal('%'.$search.'%')),
                    $qb->expr()->like('p.name_plural', $qb->expr()->literal('%'.$search.'%')),
                    $qb->expr()->like('p.name_ca_plural', $qb->expr()->literal('%'.$search.'%')),
                    $qb->expr()->like('p.name_es_plural', $qb->expr()->literal('%'.$search.'%')),
                ))
                ->andWhere('p.status = :status')
                ->setparameter('status', ProductKind::STATUS_REVIEWED)
                ->getQuery()
                ->getResult();

            $result = $this->secureOutput($products);

            return $this->rest(
                self::HTTP_STATUS_CODE_OK,
                "ok",
                "Request successful",
                array(
                    'total' => count($result),
                    'elements' => $result
                )
            );
        }

        if($request->query->has('activity')) {
            if($role !== 'admin'){
                throw new HttpException(403, 'You do not have the necessary permissions to perform this action');
            }

            $activity_id = $request->query->get('activity');

            $em = $this->getEntityManager();
            $qb = $em->createQueryBuilder();

            $products = $qb->select('p')
                ->from(ProductKind::class, 'p')
                ->leftJoin('p.activities', 'ap')
                ->where('ap.id LIKE :activity')
                ->setParameter('activity', $activity_id)
                ->getQuery()
                ->getResult();
            $result = $this->secureOutput($products);

            return $this->rest(
                self::HTTP_STATUS_CODE_OK,
                "ok",
                "Request successful",
                array(
                    'total' => count($result),
                    'elements' => $result
                )
            );
        }

        return parent::searchAction($request, $role);
    }

    public function createAction(Request $request, $role)
    {
        if($role !== 'admin'){
            //look for existing product
            /** @var User $user */
            $user = $this->getUser();
            /** @var Group $account */
            $account = $user->getActiveGroup();
            $id_account = $account->getId();
            $em = $this->getEntityManager();

            if(!$request->request->has('name')){
                throw new HttpException(403, 'Param name is mandatory');
            }

            if(!$request->request->has('type')){
                throw new HttpException(403, 'Param type is mandatory');
            }

            $name = $request->request->get('name');
            $type = $request->request->get('type');

            $products = $this->findProductsByNameAndActivity($name, $type, $id_account);

            if(count($products) > 0){
                //we take the first onw
                $product = $products[0];
            }else{
                //We did not find any product then we need to create a new one
                $product = new ProductKind();

                $product->setName($name);
                $product->setNameEs($name);
                $product->setNameCa($name);
                $product->setNamePlural($name);
                $product->setNameEsPlural($name);
                $product->setNameCaPlural($name);

                $em->persist($product);
                $em->flush();
            }

            //Now we need to add this product to this account
            switch ($type){
                case 'producing':
                    $account->addProducingProduct($product);
                    break;
                case 'consuming':
                    $account->addConsumingProduct($product);
                    break;
                default:
                    throw new HttpException(403, 'Incorrect value for type, Allowed consuming/producing');
            }

            $em->flush();

            $output = $this->secureOutput($product);
            return $this->rest(
                static::HTTP_STATUS_CODE_CREATED,
                "ok",
                "Created successfully",
                $output
            );

        }
        return parent::createAction($request, $role);
    }

    public function indexAction(Request $request, $role)
    {
        if($role !== self::ROLE_SUPER_ADMIN){
            $request->query->set('status', ProductKind::STATUS_REVIEWED);
        }
        return parent::indexAction($request, $role);
    }

    private function findProductsByName($name){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        return $qb->select('p')
            ->from(ProductKind::class,'p')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('p.name', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_es', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_ca', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_plural', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_es_plural', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_ca_plural', $qb->expr()->literal($name)),
            ))
            ->andWhere('p.status = :status')
            ->setparameter('status', ProductKind::STATUS_REVIEWED)
            ->getQuery()
            ->getResult();
    }

    private function findProductsByNameAndActivity($name, $type, $id_account){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $table = '';
        if($type == 'producing'){
            $table = 'p.producing_by';
        }
        else $table = 'p.consuming_by';

        return $qb->select('p')
            ->from(ProductKind::class,'p')
            ->leftJoin($table, 'pp')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('p.name', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_es', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_ca', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_plural', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_es_plural', $qb->expr()->literal($name)),
                $qb->expr()->eq('p.name_ca_plural', $qb->expr()->literal($name)),
            ))
            ->andWhere('pp.id = :group')
            ->setParameter('group', $id_account)
            ->getQuery()
            ->getResult();
    }

    public function searchActivityAction(Request $request, $role){

        if($role !== 'admin'){
            throw new HttpException(403, 'You do not have the necessary permissions to perform this action');
        }
        if($request->query->has('activity')){
            $activity_id = $request->query->get('activity');
            if(!$activity_id) throw new HttpException(403, 'No activity selected');
            $em = $this->getEntityManager();
            $qb = $em->createQueryBuilder();

            $products = $qb->select('p')
                ->from(ProductKind::class, 'p')
                ->leftJoin('p.activities', 'ap')
                ->where('ap.id LIKE :activity')
                ->setParameter('activity', $activity_id)
                ->getQuery()
                ->getResult();
            $result = $this->secureOutput($products);

            return $this->rest(
                self::HTTP_STATUS_CODE_OK,
                "ok",
                "Request successful",
                $result
            );
        }
    }

    public function existsAction(Request $request, $role)
    {

        if(!$request->request->has('name') &&
            !$request->request->has('name_es') &&
            !$request->request->has('name_cat') &&
            !$request->request->has('name_plural') &&
            !$request->request->has('name_es_plural') &&
            !$request->request->has('name_cat_plural'))
            throw new HttpException(403, 'Param some type of name is required');

            $name = $request->request->get('name');
            $name_es = $request->request->get('name_es');
            $name_cat = $request->request->get('name_cat');
            $name_plural = $request->request->get('name_plural');
            $name_es_plural = $request->request->get('name_es_plural');
            $name_cat_plural = $request->request->get('name_cat_plural');

            $em = $this->getEntityManager();
            $qb = $em->createQueryBuilder();

            $products = $qb->select('p')
                ->from(ProductKind::class,'p')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('p.name', $qb->expr()->literal($name)),
                    $qb->expr()->eq('p.name_es', $qb->expr()->literal($name_es)),
                    $qb->expr()->eq('p.name_ca', $qb->expr()->literal($name_cat)),
                    $qb->expr()->eq('p.name_plural', $qb->expr()->literal($name_plural)),
                    $qb->expr()->eq('p.name_es_plural', $qb->expr()->literal($name_es_plural)),
                    $qb->expr()->eq('p.name_ca_plural', $qb->expr()->literal($name_cat_plural)),
                ))
                ->andWhere('p.status = :status')
                ->setparameter('status', ProductKind::STATUS_REVIEWED)
                ->getQuery()
                ->getResult();

            $result = $this->secureOutput($products);

            return $this->rest(
                self::HTTP_STATUS_CODE_OK,
                "ok",
                "Request successful",
                array(
                    'total' => count($result),
                    'elements' => $result
                )
            );
    }

    private function getEntityManager(){
        return $this->get('doctrine.orm.entity_manager');
    }
}
