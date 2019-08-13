<?php

namespace App\FinancialApiBundle\Controller\CRUD;

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
    const PATH_ENTITY_MAPPINGS = [
        'accounts' => 'Group',
        'activities' => 'Activity',
        'treasure_withdrawals' => 'TreasureWithdrawalAttempt',
        'treasure_validations' => 'TreasureWithdrawalValidation',
    ];

    function getRepositoryName()
    {
        $entityName = $this->getEntityName();
        return self::BASE_REPOSITORY_NAME . ":" . $entityName;
    }

    private function getEntityName(){
        /** @var RequestStack $request */
        $request = $this->get("request_stack");
        $parts = explode("/", $request->getCurrentRequest()->getPathInfo());
        return $this->transformPathToEntityName($parts[3]);
    }

    private function transformPathToEntityName($lowercase_pluralized_name){
        if(key_exists($lowercase_pluralized_name, self::PATH_ENTITY_MAPPINGS)) {
            return self::PATH_ENTITY_MAPPINGS[$lowercase_pluralized_name];
        }
        else {
            $nameConverter = new CamelCaseToSnakeCaseNameConverter(null, false);
            $camelCasedName = $nameConverter->denormalize($lowercase_pluralized_name);
            return substr($camelCasedName, 0, strlen($camelCasedName) - 1);
        }

    }

    function getNewEntity() {
        $entityName = $this->getEntityName();
        try {
            $rc = new ReflectionClass('App\\FinancialApiBundle\\Entity\\' . $entityName);
        } catch (ReflectionException $e) {
            throw new HttpException(404, "Route not found");
        }
        return $rc->newInstance();
    }

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_METHOD_SEARCH => self::ROLE_PUBLIC,
            self::CRUD_METHOD_EXPORT => self::ROLE_SUPER_ADMIN,
            self::CRUD_METHOD_INDEX => self::ROLE_USER,
            self::CRUD_METHOD_SHOW => self::ROLE_USER,
            self::CRUD_METHOD_CREATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_METHOD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_METHOD_DELETE => self::ROLE_SUPER_ADMIN,
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

    public function updateAction(Request $request, $role, $id)
    {
        return parent::updateAction($request, $role, $id);
    }

    public function deleteAction($role, $id)
    {
        return parent::deleteAction($role, $id);
    }
}
