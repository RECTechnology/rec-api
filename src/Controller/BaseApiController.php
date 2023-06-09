<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace App\Controller;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use JMS\Serializer\SerializationContext;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

abstract class BaseApiController extends RestApiController implements RepositoryController {

    const HTTP_STATUS_CODE_OK = 200;
    const HTTP_STATUS_CODE_CREATED = 201;

    use SecurityTrait;

    /**
     * @return ObjectRepository
     */
    protected function getRepository(){
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        return $em->getRepository($this->getRepositoryName());
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

    protected function indexAction(Request $request){

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

            return $this->rest(
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

    protected function showAction($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");
        return $this->rest(200,"ok", "Request successful", $this->findObject($id));
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

        if(count($errors) > 0)  return $this->rest(400, "error", "Validation error", $errors);

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

        $ctx = new SerializationContext();
        $ctx->enableMaxDepthChecks();
        $resp = $this->get('jms_serializer')->toArray($entity, $ctx);

        return $this->rest($httpCode,"ok", "Created successfully", $resp);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    protected function createAction(Request $request){

        $entity = $this->getNewEntity();
        $params = $request->request->all();

        return $this->setAction($entity, $params, static::HTTP_STATUS_CODE_CREATED);
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     */
    protected function updateAction(Request $request, $id){

        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $params = $request->request->all();

        $repo = $this->getRepository();

        $entity = $repo->findOneBy(['id' => $id]);

        if(empty($entity)) throw new HttpException(404, "Not found");

        return $this->setAction($entity, $params, 200);
    }

    protected function deleteAction($id){

        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entity = $repo->find($id);

        if(empty($entity)) throw new HttpException(404, "Not found");

        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->rest(200,"ok", "Deleted successfully");
    }


    protected function getSetter($attribute) {
        return $this->getAccessor('set', $attribute);
    }

    protected function getGetter($attribute) {
        return $this->getAccessor('get', $attribute);
    }

    protected function getAdder($attribute) {
        return $this->getAccessor('add', $attribute);
    }

    protected function getDeleter($attribute) {
        return $this->getAccessor('del', $attribute);
    }

    protected function getAccessor($prefix, $attribute) {
        $accessor = $this->toCamelCase($prefix . "_" . $attribute);
        if(substr($accessor,strlen($accessor) - 3) === 'ies')
            return substr($accessor, 0, strlen($accessor) - 3) . 'y';
        if(substr($accessor,strlen($accessor) - 1) === 's')
            return substr($accessor, 0, strlen($accessor) - 1);
        return $accessor;
    }

    protected function toCamelCase($str) {
        $nameConverter = new CamelCaseToSnakeCaseNameConverter(null, false);
        return $nameConverter->denormalize($str);
    }

    protected function attributeToSetter($str) {
        return $this->getSetter($str);
    }
}