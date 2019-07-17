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
use Doctrine\DBAL\Platforms\Keywords\OracleKeywords;
use Doctrine\ORM\Mapping\AttributeOverride;
use Doctrine\ORM\Mapping\AttributeOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Telepay\FinancialApiBundle\Entity\Group;

abstract class BaseApiV2Controller extends RestApiController implements RepositoryController {

    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_CREATED = 201;

    const CRUD_METHOD_SEARCH = "SEARCH";
    const CRUD_METHOD_EXPORT = "EXPORT";
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

    /**
     * @return ObjectRepository
     */
    protected function getRepository(){
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        return $em->getRepository($this->getRepositoryName());
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

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');

        /** @var AuthorizationCheckerInterface $auth */
        $auth = $this->get('security.authorization_checker');

        if($tokenStorage->getToken()) {
            if (!$auth->isGranted(self::ROLE_PATH_MAPPINGS[$role]))
                throw new HttpException(403, "Insufficient permissions for $role");
        }
        $grants = $this->getCRUDGrants();
        if(isset($grants[$method])) {
            if(!$tokenStorage->getToken() and $grants[$method] === self::ROLE_PUBLIC)
                return;
            if (!$auth->isGranted($grants[$method]))
                throw new HttpException(403, "Insufficient permissions to $method this resource");
        }
        elseif(!$tokenStorage->getToken())
            throw new HttpException(401, "You are not authenticated");
        else
            throw new HttpException(403, "Insufficient permissions to $method this resource");

    }

    /**
     * @return SerializationContext
     */
    private function getSerializationContext() {

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');

        /** @var AuthorizationCheckerInterface $auth */
        $auth = $this->get('security.authorization_checker');

        $grantsMap = [
            'ROLE_SUPER_ADMIN' => Group::SERIALIZATION_GROUPS_SUPER_ADMIN,
            'ROLE_ADMIN' => Group::SERIALIZATION_GROUPS_ADMIN,
            'ROLE_SELF' => Group::SERIALIZATION_GROUPS_SELF,
            'ROLE_MANAGER' => Group::SERIALIZATION_GROUPS_MANAGER,
            'ROLE_USER' => Group::SERIALIZATION_GROUPS_USER,
            'IS_AUTHENTICATED_ANONYMOUSLY' => Group::SERIALIZATION_GROUPS_PUBLIC,
        ];

        $ctx = new SerializationContext();
        if($tokenStorage->getToken()){
            foreach($grantsMap as $grant => $serializationGroup){
                if($auth->isGranted($grant)) {
                    $ctx->setGroups($serializationGroup);
                    return $ctx;
                }
            }
        }

        $ctx->setGroups(Group::SERIALIZATION_GROUPS_PUBLIC);
        return $ctx;
    }

    protected function securizeOutput($result){

        $ctx = $this->getSerializationContext();


        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        return $serializer->toArray($result, $ctx);
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

        list($total, $result) = $this->index($request);
        $elems = $this->securizeOutput($result);

        return $this->restV2(
            self::HTTP_STATUS_CODE_OK,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $elems
            )
        );
    }


    /**
     * @param Request $request
     * @return array
     */
    public function indexUnlimited(Request $request) {
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        if($offset < 0) throw new HttpException(400, "Invalid offset: must positive or zero");
        $sort = $request->query->get('sort', "id");
        $order = $request->query->get('order', "DESC");
        $search = $request->query->get('search', "");


        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $className = $this->getRepository()->getClassName();

        $trueExpr = $qb->expr()->eq($qb->expr()->literal(1), $qb->expr()->literal(1));

        # Key-Value filter
        $kvFilter = $qb->expr()->andX();
        foreach ($request->query->keys() as $key){
            if(substr($key, -3) === "_id") {
                $name = substr($key, 0, strlen($key) - 3);
                $kvFilter->add($qb->expr()->eq('IDENTITY(e.' . $name . ')', $request->query->get($key)));
            }
            elseif(property_exists($className, $key)){
                $kvFilter->add($qb->expr()->eq('e.' . $key, "'" . $request->query->get($key) . "'"));
            }
        }
        # Adding always-true expression to avoid kvFilter to be empty
        if($kvFilter->count() <= 0) $kvFilter->add($trueExpr);

        # Search filter
        $searchFilter = $qb->expr()->orX();
        if($search !== "") {
            $properties = $em->getClassMetadata($this->getRepository()->getClassName())->getFieldNames();
            foreach ($properties as $property) {
                $searchFilter->add(
                    $qb->expr()->like(
                        'e.' . $property,
                        $qb->expr()->literal('%' . $search . '%')
                    )
                );
            }
        }
        # Adding always-true expression to avoid searchFilter to be empty
        if($kvFilter->count() <= 0) $searchFilter->add($trueExpr);

        $where = $qb->expr()->andX();
        $where->add($kvFilter);
        $where->add($searchFilter);

        $qb = $qb->from($className, 'e');
        $qb = $qb->where($where);
        //die($qb->getDQL());
        try {
            $total = $qb
                ->select('count(e.id)')
                ->getQuery()
                ->getSingleScalarResult();
            $result = $qb
                ->select('e')
                ->orderBy('e.' . $sort, $order)
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            return [intval($total), $result];

        } catch (NoResultException $e) {
            return [0, []];
        } catch (NonUniqueResultException $e) {
            throw new HttpException(400, "Invalid params, please check query");
        }

    }

    /**
     * @param Request $request
     * @return array
     */
    public function index(Request $request) {
        $limit = $request->query->get('limit', 10);
        if($limit < 0 or $limit > 100) throw new HttpException(400, "Invalid limit: must be between 1 and 100");
        return $this->indexUnlimited($request);
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
        $entity = $this->show($id);
        $output = $this->securizeOutput($entity);
        return $this->restV2(self::HTTP_STATUS_CODE_OK,
            "ok",
            "Request successful",
            $output
        );
    }

    /**
     * @param $id
     * @return object|null
     */
    public function show($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        return $this->findObject($id);

    }

    /**
     * @param $entity
     * @param $params
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    private function setAction($entity, $params){

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

        return $entity;
    }

    /**
     * @param Request $request
     * @param $role
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    protected function createAction(Request $request, $role){
        $this->checkPermissions($role, self::CRUD_METHOD_CREATE);
        $entity = $this->create($request);
        $output = $this->securizeOutput($entity);
        return $this->restV2(
            static::HTTP_STATUS_CODE_CREATED,
            "ok",
            "Created successfully",
            $output
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function create(Request $request){
        $entity = $this->getNewEntity();
        $params = $request->request->all();
        return $this->setAction($entity, $params);
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
        $entity = $this->update($request, $id);
        $output = $this->securizeOutput($entity);
        return $this->restV2(
            static::HTTP_STATUS_CODE_OK,
            "ok",
            "Updated successfully",
            $output
        );

    }

    /**
     * @param Request $request
     * @param $id
     * @return object|null
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function update(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $params = $request->request->all();

        $repo = $this->getRepository();

        $entity = $repo->findOneBy(['id' => $id]);

        if(empty($entity)) throw new HttpException(404, "Not found");

        return $this->setAction($entity, $params);
    }

    /**
     * @param $role
     * @param $id
     * @return Response
     */
    protected function deleteAction($role, $id){
        $this->checkPermissions($role, self::CRUD_METHOD_DELETE);
        return $this->delete($id);
    }

    /**
     * @param $id
     * @return Response
     */
    public function delete($id){
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

    /**
     * @param Request $request
     * @return mixed
     */
    public function search(Request $request) {
        return $this->index($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request) {
        return $this->indexUnlimited($request);
    }

    /**
     * @param Request $request
     * @param $role
     * @return mixed
     */
    protected function searchAction(Request $request, $role) {
        $this->checkPermissions($role, self::CRUD_METHOD_SEARCH);
        list($total, $result) = $this->search($request);
        $elems = $this->securizeOutput($result);

        return $this->restV2(
            self::HTTP_STATUS_CODE_OK,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $elems
            )
        );
    }

    /**
     * @param Request $request
     * @param $role
     * @return mixed
     */
    protected function exportAction(Request $request, $role) {
        $this->checkPermissions($role, self::CRUD_METHOD_SEARCH);
        $request->query->set("limit", 2**31);
        $fieldMap = json_decode($request->query->get("field_map", "{}"), true);
        if(json_last_error()) throw new HttpException(400, "Bad field_map, it must be a valid JSON");
        list($total, $result) = $this->export($request);
        $elems = $this->securizeOutput($result);

        $fp = fopen('php://output', 'w');
        $export = [array_keys($fieldMap)];
        foreach($elems as $el){
            try {
                $obj = new JsonObject($el);
            } catch (InvalidJsonException $e) {
                throw new HttpException(400, "Invalid JSON: " . $e->getMessage());
            }
            $exportRow = [];
            foreach($fieldMap as $jsonPath){
                try {
                    $found = $obj->get($jsonPath);
                } catch (Exception $e) {
                    throw new HttpException(400, "Invalid JsonPath: " . $e->getMessage());
                }
                if(count($found) == 0)
                    $exportRow []= null;
                elseif(count($found) == 1) {
                    if(is_array($found[0]))
                        throw new HttpException(
                            400,
                            "Error with JSONPath '$jsonPath': every field must return single value, it returns " . json_encode($found[0])
                        );
                    $exportRow [] = $found[0];
                }
                else throw new HttpException(400, "Invalid JsonPath query: every field must return only one field");
            }
            $export []= $exportRow;
        }

        foreach($export as $row){
            fputcsv($fp, $row);
        }


        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');

        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');
        return $response;
    }
}