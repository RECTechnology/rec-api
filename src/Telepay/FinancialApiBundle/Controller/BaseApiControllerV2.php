<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use JMS\Serializer\SerializationContext;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Telepay\FinancialApiBundle\Entity\Group;

abstract class BaseApiControllerV2 extends RestApiController implements RepositoryController {

    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_CREATED = 201;


    const CRUD_METHOD_SEARCH = "SEARCH";
    const CRUD_METHOD_INDEX = 'INDEX';
    const CRUD_METHOD_SHOW = 'SHOW';
    const CRUD_METHOD_CREATE = 'CREATE';
    const CRUD_METHOD_UPDATE = 'UPDATE';
    const CRUD_METHOD_DELETE = 'DELETE';

    const ROLE_SUPER_ADMIN = "ROLE_SUPER_ADMIN";
    const ROLE_MANAGER = "ROLE_MANAGER";
    const ROLE_ADMIN = "ROLE_ADMIN";
    const ROLE_USER = "ROLE_USER";
    const ROLE_SELF = "ROLE_SELF";
    const ROLE_PUBLIC = "ROLE_PUBLIC";

    const ROLE_PATH_MAPPINGS = [
        'public' => self::ROLE_PUBLIC,
        'user' => self::ROLE_USER,
        'manager' => self::ROLE_MANAGER,
        'self' => self::ROLE_SELF,
        'admin' => self::ROLE_ADMIN,
        'sadmin' => self::ROLE_SUPER_ADMIN,
    ];

    protected function getRepository(){
        return $this->getDoctrine()
            ->getManager()
            ->getRepository($this->getRepositoryName());
    }

    /**
     * @return array
     */
    abstract function getCRUDGrants();

    /**
     * @param $role
     * @param $method
     */
    protected function checkPermissions($role, $method){
        if(!in_array($role, array_keys(self::ROLE_PATH_MAPPINGS)))
            throw new HttpException(404, "Path not found");

        /** @var SecurityContextInterface $sec */
        $sec = $this->get('security.context');
        if($sec->getToken()) {
            if (!$sec->isGranted(self::ROLE_PATH_MAPPINGS[$role]))
                throw new HttpException(403, "Insufficient permissions for $role");
        }
        $grants = $this->getCRUDGrants();
        if(isset($grants[$method])) {
            if(!$sec->getToken() and $grants[$method] === self::ROLE_PUBLIC)
                return;
            if (!$sec->isGranted($grants[$method]))
                throw new HttpException(403, "Insufficient permissions to $method this resource");
        }
        elseif(!$sec->getToken())
            throw new HttpException(401, "You are not authenticated");
        else
            throw new HttpException(403, "Insufficient permissions to $method this resource");

    }

    /**
     * @return SerializationContext
     */
    protected function getSerializationContext() {
        $ctx = new SerializationContext();

        /** @var SecurityContextInterface $sec */
        $sec = $this->get('security.context');

        if(!$sec->getToken())
            $ctx->setGroups(Group::SERIALIZATION_GROUPS_PUBLIC);
        elseif($sec->isGranted('ROLE_SUPER_ADMIN'))
            $ctx->setGroups(Group::SERIALIZATION_GROUPS_ADMIN);
        elseif ($sec->isGranted('ROLE_MANAGER'))
            $ctx->setGroups(Group::SERIALIZATION_GROUPS_MANAGER);
        elseif ($sec->isGranted('ROLE_USER'))
            $ctx->setGroups(Group::SERIALIZATION_GROUPS_USER);
        else
            $ctx->setGroups(Group::SERIALIZATION_GROUPS_PUBLIC);

        return $ctx;
    }

    /**
     * @param $key
     * @param $sent_value
     * @return object|null
     * @throws AnnotationException
     * @throws ReflectionException
     */
    private function findForeignObject($key, $sent_value){

        $className = $this->getRepository()->getClassName();
        $name = substr($key, 0, strlen($key) - 3);
        if(!property_exists($className, $name))
            throw new HttpException(400, "Bad request, parameter '$key' is invalid.");
        $reflectionProperty = new ReflectionProperty($className, $name);
        $ar = new AnnotationReader();
        $propertyAnnotations = $ar->getPropertyAnnotations($reflectionProperty);

        $rel = false;
        foreach ($propertyAnnotations as $an){
            if($an instanceof ManyToMany or $an instanceof ManyToOne or $an instanceof OneToOne){
                $rel = $an;
                break;
            }
        }

        if(!$rel) throw new HttpException(400, "Unrelated parameter");

        $value = $this->getDoctrine()->getRepository($rel->targetEntity)->find($sent_value);
        if(!$value) throw new HttpException(400, "Object $name with id '$sent_value' does not exist.");
        return $value;
    }

    /**
     * @param Request $request
     * @param $role
     * @return Response
     */
    protected function indexAction(Request $request, $role){
        $this->checkPermissions($role, self::CRUD_METHOD_INDEX);

        $limit = $request->query->get('limit', 10);
        if($limit < 0 or $limit > 100) throw new HttpException(400, "Invalid limit: must be between 1 and 100");
        $offset = $request->query->get('offset', 0);
        if($offset < 0) throw new HttpException(400, "Invalid offset: must positive or zero");

        $sort = $request->query->get('sort', "id");
        $order = $request->query->get('order', "DESC");


        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $className = $this->getRepository()->getClassName();
        $filter = $qb->expr()->andX();
        $filter_is_empty = true;
        foreach ($request->query->keys() as $key){
            if(substr($key, -3) === "_id") {
                $name = substr($key, 0, strlen($key) - 3);
                $filter_is_empty = false;
                $filter->add($qb->expr()->eq('IDENTITY(e.' . $name . ')', $request->query->get($key)));
            }
            elseif(property_exists($className, $key)){
                $filter_is_empty = false;
                $filter->add($qb->expr()->eq('e.' . $key, "'" . $request->query->get($key) . "'"));
            }
        }

        $qb = $qb->from($className, 'e');
        if(!$filter_is_empty) $qb = $qb->where($filter);
        $qb = $qb->orderBy('e.' . $sort, $order);

        try {
            $total = intval($qb->select('count(e.id)')
                ->getQuery()->getSingleScalarResult());
            $elems = $qb->select('e')
                ->setFirstResult($offset)->setMaxResults($limit)
                ->getQuery()->getResult();

            return $this->restV2(
                200,
                "ok",
                "Request successful",
                array(
                    'total' => $total,
                    'elements' => $elems
                )
            );

        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        } catch (QueryException $e) {
            throw new HttpException(400, "Invalid params, please check query");
        }

    }

    protected function findObject($id){
        $repo = $this->getRepository();
        $entity = $repo->find($id);
        if(empty($entity)) throw new HttpException(404, "Not found");
        return $entity;
    }

    /**
     * @param $role
     * @param $id
     * @return Response
     */
    protected function showAction($role, $id){
        $this->checkPermissions($role, self::CRUD_METHOD_SHOW);
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        return $this->restV2(200,"ok", "Request successful", $this->findObject($id));
    }


    /**
     * @param $entity
     * @param $params
     * @param $httpCode
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    private function setAction($entity, $params, $httpCode){

        $ar = new AnnotationReader();
        foreach ($params as $name => $value) {


            // user is trying to set id, but it is autogenerated
            if ($name === 'id') {
                throw new HttpException(400, "Cannot set 'id': it is auto-generated and read-only");
            }

            // user is trying to set foreign key
            elseif(substr($name, -3) === "_id"){
                $value = $this->findForeignObject($name, $value);
                $name = substr($name, 0, strlen($name) - 3);
            }
            else {
                $reflectionProperty = new ReflectionProperty($this->getRepository()->getClassName(), $name);
                $propertyAnnotations = $ar->getPropertyAnnotations($reflectionProperty);

                foreach ($propertyAnnotations as $an){
                    if($an instanceof ManyToMany or $an instanceof ManyToOne or $an instanceof OneToOne){
                        throw new HttpException(400, "Use suffix '_id' to set related properties: '${name}_id': $value");
                    }
                }
            }

            $setter = $this->attributeToSetter($name);

            if (method_exists($entity, $setter)) {
                call_user_func_array(array($entity, $setter), array($value));
            }
            else{
                throw new HttpException(400, "Bad request, parameter '$name' is invalid. ");
            }

        }
        $em = $this->getDoctrine()->getManager();
        $errors = $this->get('validator')->validate($entity);

        if(count($errors) > 0)  return $this->restV2(400, "error", "Validation error", $errors);

        $em->persist($entity);
        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/1062 Duplicate entry/i',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else if(preg_match('/1048 Column/i',$e->getMessage()))
                throw new HttpException(400, "Bad parameters: " . $e->getMessage());
            throw new HttpException(500, "Unknown error occurred when save: " . $e->getMessage());
        }

        return $this->restV2($httpCode,"ok", "Created successfully", $entity);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    protected function createAction(Request $request, $role){
        $this->checkPermissions($role, self::CRUD_METHOD_CREATE);

        $entity = $this->getNewEntity();
        $params = $request->request->all();
        return $this->setAction($entity, $params, static::HTTP_STATUS_CODE_CREATED);
    }

    /**
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    protected function updateAction(Request $request, $role, $id){
        $this->checkPermissions($role, self::CRUD_METHOD_UPDATE);

        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $params = $request->request->all();

        $repo = $this->getRepository();

        $entity = $repo->findOneBy(['id' => $id]);

        if(empty($entity)) throw new HttpException(404, "Not found");
        return $this->setAction($entity, $params, 200);
    }

    /**
     * @param $role
     * @param $id
     * @return Response
     */
    protected function deleteAction($role, $id){
        $this->checkPermissions($role, self::CRUD_METHOD_DELETE);

        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entity = $repo->find($id);

        if(empty($entity)) throw new HttpException(404, "Not found");

        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->restV2(200,"ok", "Deleted successfully");
    }


    private function toCamelCase($str) {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }


    private function attributeToSetter($str) {
        return $this->toCamelCase("set_" . $str);
    }
}