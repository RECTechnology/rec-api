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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolation;

abstract class BaseApiController extends RestApiController implements RepositoryController {

    const HTTP_STATUS_CODE_CREATED = 201;

    protected function getRepository(){
        return $this->getDoctrine()
            ->getManager()
            ->getRepository($this->getRepositoryName());
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
        $offset = $request->query->get('offset', 0);

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
            $total = $qb->select('count(e.id)')
                ->getQuery()->getSingleScalarResult();
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
        }

    }

    protected function showAction($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entities = $repo->findOneBy(array('id'=>$id));

        if(empty($entities)) throw new HttpException(404, "Not found");

        return $this->restV2(200,"ok", "Request successful", $entities);
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
                throw new HttpException(400, "Bad request, parameter '$name' is invalid.");
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

        $entity = $repo->findOneBy(['id' => $id]);

        if(empty($entity)) throw new HttpException(404, "Not found");

        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->restV2(200,"ok", "Deleted successfully", array());
    }


    private function toCamelCase($str) {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }


    private function attributeToSetter($str) {
        return $this->toCamelCase("set_" . $str);
    }
}