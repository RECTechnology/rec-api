<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace App\FinancialApiBundle\Controller;

use App\FinancialApiBundle\Exception\AppException;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Exception\ValidationFailedException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Validator\ConstraintViolation;
use App\FinancialApiBundle\Entity\Group;

abstract class BaseApiV2Controller extends RestApiController implements RepositoryController {

    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_CREATED = 201;

    const MAX_ELEMENTS_IN_GET = 500;

    const CRUD_SEARCH = "SEARCH";
    const CRUD_EXPORT = "EXPORT";
    const CRUD_INDEX = 'INDEX';
    const CRUD_SHOW = 'SHOW';
    const CRUD_CREATE = 'CREATE';
    const CRUD_UPDATE = 'UPDATE';
    const CRUD_DELETE = 'DELETE';

    const ROLE_ORGANIZATION = "ROLE_COMPANY";

    const ROLE_ROOT = "ROLE_ROOT";
    const ROLE_SUPER_MANAGER = "ROLE_SUPER_MANAGER";
    const ROLE_SUPER_ADMIN = "ROLE_SUPER_ADMIN";
    const ROLE_SUPER_USER = "ROLE_SUPER_USER";
    const ROLE_PUBLIC = "ROLE_PUBLIC";

    const ROLE_PATH_MAPPINGS = [
        'public' => self::ROLE_PUBLIC,
        'user' => self::ROLE_SUPER_USER,
        'manager' => self::ROLE_SUPER_MANAGER,
        'admin' => self::ROLE_SUPER_ADMIN,
        'root' => self::ROLE_ROOT,
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
            if (!$auth->isGranted(self::ROLE_PATH_MAPPINGS[$role])){
                throw new HttpException(
                    Response::HTTP_FORBIDDEN,
                    "Insufficient permissions for $role"
                );
            }
        }
        $grants = $this->getCRUDGrants();
        if(isset($grants[$method])) {
            if(!$tokenStorage->getToken() and $grants[$method] === self::ROLE_PUBLIC)
                return;
            if (!$auth->isGranted($grants[$method]))
                throw new HttpException(
                    Response::HTTP_FORBIDDEN,
                    "Insufficient permissions to $method this resource."
                );
        }
        elseif(!$tokenStorage->getToken())
            throw new HttpException(
                Response::HTTP_UNAUTHORIZED,
                "You are not authenticated"
            );
        else
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                "Insufficient permissions to $method this resource."
            );

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
            self::ROLE_ROOT => Group::SERIALIZATION_GROUPS_ROOT,
            self::ROLE_SUPER_ADMIN => Group::SERIALIZATION_GROUPS_ADMIN,
            self::ROLE_SUPER_MANAGER => Group::SERIALIZATION_GROUPS_MANAGER,
            self::ROLE_SUPER_USER => Group::SERIALIZATION_GROUPS_USER,
            'IS_AUTHENTICATED_ANONYMOUSLY' => Group::SERIALIZATION_GROUPS_PUBLIC,
        ];

        $ctx = new SerializationContext();
        $ctx->enableMaxDepthChecks();
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
     * @param $relationshipNameWithId
     * @param $targetId
     * @return object|null
     * @throws AnnotationException
     */
    private function findRelatedObjectWithRelId($relationshipNameWithId, $targetId){

        $name = substr($relationshipNameWithId, 0, strlen($relationshipNameWithId) - 3);
        $relatedEntity = $this->getRelatedEntity($name);

        $value = $this->getDoctrine()->getRepository($relatedEntity)->show($targetId);
        if(!$value) throw new HttpException(
            Response::HTTP_BAD_REQUEST,
            "Object $name with id '$targetId' does not exist."
        );
        return $value;
    }

    /**
     * @param $propertyName
     * @return object|null
     * @throws AnnotationException
     */
    private function getRelatedEntity($propertyName){

        $className = $this->getRepository()->getClassName();
        if(!property_exists($className, $propertyName))
            throw new HttpException(400, "Bad request, parameter '$propertyName' is invalid.");
        try {
            $reflectionProperty = new ReflectionProperty($className, $propertyName);
            $ar = new AnnotationReader();
            $propertyAnnotations = $ar->getPropertyAnnotations($reflectionProperty);

            $rel = false;
            foreach ($propertyAnnotations as $an){
                if($an instanceof ManyToMany or $an instanceof ManyToOne or $an instanceof OneToOne or $an instanceof OneToMany){
                    $rel = $an;
                    break;
                }
            }

            if(!$rel) throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                "Unrelated parameter '$propertyName'"
            );
            return $rel->targetEntity;

        } catch (ReflectionException $e) {
            throw new HttpException(400, "Bad request, parameter '$propertyName' is invalid.", $e);
        }
    }

    /**
     * @param Request $request
     * @param $role
     * @return Response
     */
    protected function indexAction(Request $request, $role){
        $this->checkPermissions($role, self::CRUD_INDEX);
        list($total, $result) = $this->index($request);
        $result = $this->securizeOutput($result);
        return $this->restV2(
            self::HTTP_STATUS_CODE_OK,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $result
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
        $order = strtoupper($request->query->get('order', "DESC"));
        $search = $request->query->get('search', "");

        if(!in_array($order, ["ASC", "DESC"]))
            throw new HttpException(400, "Invalid order: it must be ASC or DESC");

        try{
            return $this->getRepository()->index($request, $search, $limit, $offset, $order, $sort);
        } catch (NonUniqueResultException $e) {
            throw new HttpException(400, "Invalid params, please check query", $e);
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    public function index(Request $request) {
        $limit = $request->query->get('limit', 10);
        if($limit < 0 or $limit > static::MAX_ELEMENTS_IN_GET)
            throw new HttpException(400, "Invalid limit: must be between 1 and " . static::MAX_ELEMENTS_IN_GET);
        return $this->indexUnlimited($request);
    }


    protected function findObject($id){
        $repo = $this->getRepository();
        $entity = $repo->show($id);
        $explodedEntityName = explode("\\", $repo->getClassName());
        $entityName = $explodedEntityName[count($explodedEntityName) - 1];
        if(empty($entity)) throw new HttpException(404, $entityName . " not found");
        return $entity;
    }

    /**
     * @param $role
     * @param $id
     * @return Response
     */
    protected function showAction($role, $id){
        $this->checkPermissions($role, self::CRUD_SHOW);
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
     * @return array
     * @throws AnnotationException
     */
    private function updateEntity($entity, $params) {

        $ar = new AnnotationReader();
        foreach ($params as $name => $value) {
            // user is trying to set id, but it is autogenerated
            if ($name == 'id' || $name == 'created' || $name == 'updated') {
                throw new HttpException(400, "Cannot set '$name': it is auto-generated and read-only");
            }

            // user is trying to set foreign key
            elseif(substr($name, -3) == "_id"){
                $value = $this->findRelatedObjectWithRelId($name, $value);
                $name = substr($name, 0, strlen($name) - 3);
            }
            else {
                try {
                    $reflectionProperty = new ReflectionProperty($this->getRepository()->getClassName(), $name);
                    $propertyAnnotations = $ar->getPropertyAnnotations($reflectionProperty);

                    foreach ($propertyAnnotations as $an){
                        if($an instanceof ManyToMany || $an instanceof ManyToOne || $an instanceof OneToOne){
                            throw new HttpException(400, "Use suffix '_id' to set related properties: '${name}_id': $value");
                        }
                        elseif($an instanceof Column && $an->type == 'datetime'){
                            $sentValue = $value;
                            $value = DateTime::createFromFormat(DateTime::ISO8601 , $sentValue);
                            if(!$value){
                                $value = DateTime::createFromFormat(DateTime::RFC3339_EXTENDED , $sentValue);
                                if(!$value) throw new HttpException(
                                    400, "Invalid datetime parameter, value must be ISO8601 or RFC3339 compliant"
                                );
                            }
                        }
                    }
                } catch (ReflectionException $e) {
                    throw new HttpException(400, "Invalid parameter '$name'", $e);
                }
            }

            $setter = $this->getSetter($name);

            if (method_exists($entity, $setter)) {
                call_user_func_array([$entity, $setter], [$value]);
            }
            else{
                throw new HttpException(400, "Bad request, parameter '$name' is invalid. ");
            }

        }
        $em = $this->getDoctrine()->getManager();
        $errors = $this->get('validator')->validate($entity);

        if(count($errors) > 0)
            throw new AppException(400, "Validation error", $errors);

        $em->persist($entity);

        $this->flush();

        return $entity;
    }

    protected function flush(){
        $em = $this->getDoctrine()->getManager();
        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/1062 Duplicate entry/i', $e->getMessage()))
                throw new HttpException(409, "Duplicated resource (duplicated entry)", $e);
            else if(preg_match('/1048 Column/i',$e->getMessage()))
                throw new HttpException(400, "Parameter(s) not allowed", $e);
            else if(preg_match('/NOT NULL constraint failed/i', $e->getMessage()))
                throw new HttpException(400, "Missed parameter(s)", $e);
            else if(preg_match('/UNIQUE constraint failed/i', $e->getMessage()))
                throw new HttpException(409, "Duplicated resource (multiple parameters duplicated)", $e);
            throw new HttpException(500, "Database error occurred when save: " . $e->getMessage(), $e);
        } catch (Exception $e){
            throw new HttpException(500, "Unknown error occurred when save: " . $e->getMessage(), $e);
        }
    }

    /**
     * @param Request $request
     * @param $role
     * @return Response
     * @throws AnnotationException
     */
    protected function createAction(Request $request, $role){
        $this->checkPermissions($role, self::CRUD_CREATE);
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
     * @return array
     * @throws AnnotationException
     */
    public function create(Request $request){
        $entity = $this->getNewEntity();
        $params = $request->request->all();
        return $this->updateEntity($entity, $params);
    }

    /**
     * @param Request $request
     * @param $role
     * @param $id
     * @return Response
     * @throws AnnotationException
     */
    protected function updateAction(Request $request, $role, $id){
        $this->checkPermissions($role, self::CRUD_UPDATE);
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
     * @return array
     * @throws AnnotationException
     */
    public function update(Request $request, $id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $params = $request->request->all();

        $repo = $this->getRepository();

        $entity = $repo->find($id);

        if(empty($entity)) throw new HttpException(404, "Not found");

        return $this->updateEntity($entity, $params);
    }


    /**
     * @param Request $request
     * @param $role
     * @param $id
     * @param $relationship
     * @return Response
     * @throws AnnotationException
     */
    protected function addRelationshipAction(Request $request, $role, $id, $relationship){
        $this->checkPermissions($role, self::CRUD_UPDATE);
        $entity = $this->addRelationship($request, $id, $relationship);
        $output = $this->securizeOutput($entity);
        return $this->restV2(
            static::HTTP_STATUS_CODE_CREATED,
            "ok",
            "Added successfully",
            $output
        );

    }

    /**
     * @param Request $request
     * @param $id
     * @param $relationship
     * @return object|null
     * @throws AnnotationException
     */
    public function addRelationship(Request $request, $id, $relationship){
        if(empty($id)) throw new HttpException(400, "Missing URL parameter 'id'");
        if(empty($relationship)) throw new HttpException(400, "Missing URL parameter 'relationship'");
        if(!$request->request->has('id')) throw new HttpException(400, "Missing POST parameter 'id'");

        $repo = $this->getRepository();

        $entity = $repo->show($id);
        if(empty($entity)) throw new HttpException(404, "Not found");

        $targetEntityName = $this->getRelatedEntity($relationship);

        $targetEntityId = $request->request->get('id', -1);
        $relatedEntity = $this
            ->getDoctrine()
            ->getRepository($targetEntityName)
            ->show($targetEntityId);
        if(empty($relatedEntity)) throw new HttpException(404, "Not found");

        $adder = $this->getAdder($relationship);

        if (!method_exists($entity, $adder)) {
            throw new HttpException(400, "Bad request, parameter '$relationship' is invalid.");
        }
        call_user_func_array([$entity, $adder], [$relatedEntity]);
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->persist($relatedEntity);
        $this->flush();
        return $entity;
    }


    /**
     * @param Request $request
     * @param $role
     * @param $id1
     * @param $relationship
     * @param $id2
     * @return Response
     * @throws AnnotationException
     */
    protected function deleteRelationshipAction(Request $request, $role, $id1, $relationship, $id2){
        $this->checkPermissions($role, self::CRUD_DELETE);
        $entity = $this->deleteRelationship($request, $id1, $relationship, $id2);
        $output = $this->securizeOutput($entity);
        return $this->restV2(
            static::HTTP_STATUS_CODE_OK,
            "ok",
            "Deleted successfully",
            $output
        );

    }

    /**
     * @param Request $request
     * @param $id1
     * @param $relationship
     * @param $id2
     * @return object|null
     * @throws AnnotationException
     */
    public function deleteRelationship(Request $request, $id1, $relationship, $id2){
        if(empty($id1)) throw new HttpException(400, "Missing URL parameter 'id1'");
        if(empty($relationship)) throw new HttpException(400, "Missing URL parameter 'relationship'");
        if(empty($id2)) throw new HttpException(400, "Missing URL parameter 'id2'");

        $repo = $this->getRepository();

        $entity = $repo->show($id1);
        if(empty($entity)) throw new HttpException(404, "Not found");

        $targetEntityName = $this->getRelatedEntity($relationship);

        $targetEntityId = $id2;
        $relatedEntity = $this
            ->getDoctrine()
            ->getRepository($targetEntityName)
            ->show($targetEntityId);
        if(empty($relatedEntity)) throw new HttpException(404, "Not found");

        $deleter = $this->getDeleter($relationship);

        if (!method_exists($entity, $deleter)) {
            throw new HttpException(400, "Bad request, parameter '$relationship' is invalid. (method $deleter)");
        }
        call_user_func_array([$entity, $deleter], [$relatedEntity]);
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->persist($relatedEntity);
        $this->flush();
        return $entity;
    }

    /**
     * @param $role
     * @param $id
     * @return Response
     */
    protected function deleteAction($role, $id){
        $this->checkPermissions($role, self::CRUD_DELETE);
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
        $nameConverter = new CamelCaseToSnakeCaseNameConverter(null, false);
        return $nameConverter->denormalize($str);
    }


    private function getSetter($attribute) {
        return $this->getAccessor('set', $attribute);
    }

    private function getGetter($attribute) {
        return $this->getAccessor('get', $attribute);
    }

    private function getAdder($attribute) {
        return $this->getAccessor('add', $attribute);
    }

    private function getDeleter($attribute) {
        return $this->getAccessor('del', $attribute);
    }

    private function getAccessor($prefix, $attribute) {
        $accessor = $this->toCamelCase($prefix . "_" . $attribute);
        if(substr($accessor,strlen($accessor) - 3) === 'ies')
            return substr($accessor, 0, strlen($accessor) - 3) . 'y';
        if(substr($accessor,strlen($accessor) - 1) === 's')
            return substr($accessor, 0, strlen($accessor) - 1);
        return $accessor;
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
        $this->checkPermissions($role, self::CRUD_SEARCH);
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
     * @throws Exception
     */
    protected function exportAction(Request $request, $role) {
        $this->checkPermissions($role, self::CRUD_SEARCH);
        $request->query->set("limit", 2**31);
        $fieldMap = json_decode($request->query->get("field_map", "{}"), true);
        if(json_last_error()) throw new HttpException(400, "Bad field_map, it must be a valid JSON");
        list($total, $result) = $this->export($request);
        $elems = $this->securizeOutput($result);

        $namer = new CamelCaseToSnakeCaseNameConverter(null, false);

        $fullClassNameParts = explode("\\", $this->getRepository()->getClassName());
        $className = $fullClassNameParts[count($fullClassNameParts) - 1];
        $underscoreName = $namer->normalize($className);
        $now = new \DateTime("now", new DateTimeZone('Europe/Madrid'));
        $dwFilename = "export-" .  $underscoreName . "s-" . $now->format('Y-m-d\TH-i-sO') . ".csv";

        $fs = new Filesystem();
        $tmpFilename = "/tmp/$dwFilename";
        $fs->touch($tmpFilename);
        $fp = fopen($tmpFilename, 'w');

        $export = [array_keys($fieldMap)];
        foreach($elems as $el){
            try {
                $obj = new JsonObject($el);
            } catch (InvalidJsonException $e) {
                throw new HttpException(400, "Invalid JSON: " . $e->getMessage(), $e);
            }
            $exportRow = [];
            foreach($fieldMap as $jsonPath){
                try {
                    $found = $obj->get($jsonPath);
                } catch (Exception $e) {
                    throw new HttpException(400, "Invalid JsonPath: " . $e->getMessage(), $e);
                }
                if(count($found) == 0)
                    $exportRow []= null;
                elseif(count($found) == 1) {
                    if(is_array($found[0])) {
                        throw new HttpException(
                            400,
                            "Error with JSONPath '$jsonPath': every field must return single value, it returns " . json_encode($found[0])
                        );
                    }
                    $exportRow []= $found[0];
                }
                else {
                    foreach($found as $v){
                        if(is_array($v)){
                            throw new HttpException(
                                400,
                                "Error with JSONPath '$jsonPath': every field must return single value, it returns " . json_encode($v)
                            );
                        }
                    }
                    $exportRow []= implode("|", $found);
                }
            }
            $export []= $exportRow;
        }

        foreach($export as $row){
            fputcsv($fp, $row, ";");
        }


        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $dwFilename . '"');
        $response->headers->set('Content-Length', filesize($tmpFilename));

        $response->setContent(file_get_contents($tmpFilename));
        $fs->remove($tmpFilename);
        return $response;
    }

}