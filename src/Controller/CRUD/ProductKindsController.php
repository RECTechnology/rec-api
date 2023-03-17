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
            $locale = $user->getLocale();

            $em = $this->getEntityManager();

            if(!$request->request->has('name')){
                throw new HttpException(403, 'Param name is mandatory');
            }

            if(!$request->request->has('type')){
                throw new HttpException(403, 'Param type is mandatory');
            }

            $name = $request->request->get('name');
            $type = $request->request->get('type');

            $products = $this->findProductsByName($name);

            if(count($products) > 0){
                //we take the first onw
                $product = $products[0];
            }else{
                //We did not find any product then we need to create a new one
                $product = new ProductKind();
                switch ($locale){
                    case 'es':
                        $product->setNameEs($name);
                        break;
                    case 'ca':
                        $product->setNameCa($name);
                        break;
                    default:
                        $product->setName($name);
                        break;
                }

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

    private function getEntityManager(){
        return $this->get('doctrine.orm.entity_manager');
    }
}
