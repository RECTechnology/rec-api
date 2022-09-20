<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Entity\Translatable;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Serializer;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use App\FinancialApiBundle\Controller\BaseApiV2Controller;
use App\FinancialApiBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\Offer;

/**
 * Class CRUDController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class CRUDController extends BaseApiV2Controller {

    const BASE_REPOSITORY_NAME = "FinancialApiBundle";
    const PATH_ENTITY_OVERRIDES = [
        'accounts' => 'Group',
        'pos' => 'Pos',
    ];

    function getRepositoryName() {
        $entityName = $this->getEntityName();
        if(!class_exists('App\\FinancialApiBundle\\Entity\\' . $entityName))
            throw new HttpException(404, "Route not found");
        return self::BASE_REPOSITORY_NAME . ":" . $entityName;
    }

    private function getEntityName(){
        /** @var RequestStack $request */
        $request = $this->get("request_stack");
        $parts = explode("/", $request->getCurrentRequest()->getPathInfo());
        return $this->transformPathToEntityName($parts[3]);
    }

    private function transformPathToEntityName($lowercase_pluralized_name){
        if(key_exists($lowercase_pluralized_name, self::PATH_ENTITY_OVERRIDES)) {
            return self::PATH_ENTITY_OVERRIDES[$lowercase_pluralized_name];
        }
        else {
            $nameConverter = new CamelCaseToSnakeCaseNameConverter(null, false);
            $camelCasedName = $nameConverter->denormalize($lowercase_pluralized_name);
            if(substr($camelCasedName, strlen($camelCasedName) - 3) == 'ies')
                return substr($camelCasedName, 0, strlen($camelCasedName) - 3) . 'y';
            if(substr($camelCasedName, strlen($camelCasedName) - 1) == 's')
                return substr($camelCasedName, 0, strlen($camelCasedName) - 1);
            return $camelCasedName;
        }

    }

    function getNewEntity() {
        $entityName = $this->getEntityName();
        try {
            $rc = new ReflectionClass('App\\FinancialApiBundle\\Entity\\' . $entityName);
        } catch (ReflectionException $e) {
            throw new HttpException(404, "Route not found", $e);
        }
        $instance = $rc->newInstance();
        return $instance;
    }

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_SEARCH => self::ROLE_USER,
            self::CRUD_INDEX => self::ROLE_USER,
            self::CRUD_SHOW => self::ROLE_USER,
            self::CRUD_EXPORT => self::ROLE_SUPER_ADMIN,
            self::CRUD_CREATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }

    public function searchAction(Request $request, $role)
    {
        return parent::searchAction($request, $role);
    }

    public function exportAction(Request $request, $role)
    {
        return parent::exportAction($request, $role);
    }

    public function exportByEmailAction(Request $request, $role)
    {
        return parent::exportByEmailAction($request, $role);
    }

    public function importAction(Request $request, $role)
    {
        return parent::importAction($request, $role);
    }

    public function indexAction(Request $request, $role)
    {
        return parent::indexAction($request, $role);
    }

    public function showAction($role, $id)
    {
        return parent::showAction($role, $id);
    }

    public function createAction(Request $request, $role)
    {
        return parent::createAction($request, $role);
    }

    public function indexRelationshipAction(Request $request, $role, $id, $relationship)
    {
        return parent::indexRelationshipAction($request, $role, $id, $relationship);
    }

    public function addRelationshipAction(Request $request, $role, $id, $relationship)
    {
        return parent::addRelationshipAction($request, $role, $id, $relationship);
    }

    public function deleteRelationshipAction(Request $request, $role, $id1, $relationship, $id2)
    {
        return parent::deleteRelationshipAction($request, $role, $id1, $relationship, $id2);
    }

    public function updateAction(Request $request, $role, $id)
    {
        return parent::updateAction($request, $role, $id);
    }

    public function deleteAction($role, $id)
    {
        return parent::deleteAction($role, $id);
    }
}
