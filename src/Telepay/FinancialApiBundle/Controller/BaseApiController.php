<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseApiController extends RestApiController implements RepositoryController {

    protected function getRepository(){
        return $this->getDoctrine()
            ->getManager()
            ->getRepository($this->getRepositoryName());
    }

    protected function indexAction(Request $request){
        //TODO: test if is used
        //throw new HttpException(400, "_pass_");
        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        //TODO: Improve performance (two queries)
        $all = $this->getRepository()->findAll();

        $total = count($all);

        $entities = array_slice($all, $offset, $limit);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $entities
            )
        );
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
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    private function setAction($entity, $params, $httpCode){

        $ar = new AnnotationReader();
        foreach ($params as $name => $value) {

            // user is trying to set id, but it is autogenerated
            if ($name === 'id') {
                throw new HttpException(400, "Invalid attribute 'id' (auto-generated)");
            }

            // user is trying to set foreign key
            elseif(substr($name, -3) === "_id"){
                $name = substr($name, 0, strlen($name) - 3);
                $reflectionProperty = new ReflectionProperty($this->getRepository()->getClassName(), $name);
                $propertyAnnotations = $ar->getPropertyAnnotations($reflectionProperty);

                /** @var ManyToOne|ManyToMany $rel */
                $rel = $propertyAnnotations[0];

                $value = $this->getDoctrine()->getRepository($rel->targetEntity)->find($value);
            }

            $setter = $this->attributeToSetter($name);

            if (method_exists($entity, $setter)) {
                call_user_func_array(array($entity, $setter), array($value));
            }
            else{
                throw new HttpException(400, "Bad request, parameter '$name' is wrong");
            }

        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        try{
            $em->flush();
        } catch(DBALException $e){
            if(preg_match('/1062 Duplicate entry/i',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else if(preg_match('/1048 Column/i',$e->getMessage()))
                throw new HttpException(400, "Bad parameters: " . $e->getMessage());
            throw new HttpException(500, "Unknown error occurred when save");
        }

        return $this->restV2($httpCode,"ok", "Created successfully", $entity);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    protected function createAction(Request $request){
        $entity = $this->getNewEntity();
        $params = $request->request->all();
        return $this->setAction($entity, $params, 201);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
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