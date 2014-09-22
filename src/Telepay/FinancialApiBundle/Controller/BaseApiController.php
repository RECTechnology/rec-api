<?php
/**
 * Created by PhpStorm.
 * User: Rick Moreno
 * Date: 7/30/14
 * Time: 8:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller;

use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseApiController extends RestApiController implements RepositoryController{

    abstract function getRepositoryName();
    abstract function getNewEntity();

    protected function getRepository(){
        return $this->getDoctrine()
            ->getManager()
            ->getRepository($this->getRepositoryName());
    }

    protected function indexAction(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        //TODO: Improve performance (two queries)
        $all = $this->getRepository()->findAll();

        $total = count($all);

        $entities = array_slice($all, $offset, $limit);

        return $this->handleRestView(
            200,
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities
            )
        );
    }

    protected function showAction($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entities = $repo->findOneBy(array('id'=>$id));

        if(empty($entities)) throw new HttpException(404, "Not found");

        return $this->handleRestView(200, "Request successful", $entities);
    }

    protected function createAction(Request $request){
        $entity = $this->getNewEntity();

        $params = $request->request->all();

        foreach ($params as $name => $value) {
            if ($name != 'id') {
                $setter = $this->attributeToSetter($name);
                if (method_exists($entity, $setter)) {
                    call_user_func_array(array($entity, $setter), array($value));
                }
                else{
                    throw new HttpException(400, "Bad request, parameter '$name' is wrong");
                }
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
                throw new HttpException(400, "Bad parameters");
            throw new HttpException(500, "Unknown error occurred when save");
        }

        return $this->handleRestView(201, "Request successful", array('id'=>$entity->getId()));
    }

    protected function updateAction(Request $request, $id){

        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $params = $request->request->all();

        $repo = $this->getRepository();

        $entity = $repo->findOneBy(array('id'=>$id));

        if(empty($entity)) throw new HttpException(404, "Not found");

        foreach ($params as $name => $value) {
            if ($name != 'id') {
                $setter = $this->attributeToSetter($name);
                if (method_exists($entity, $setter)) {
                    call_user_func(array($entity, $setter), $value);
                }
                else{
                    throw new HttpException(400, "Bad request, parameter '$name' is not allowed");
                }
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
                throw new HttpException(400, "Bad parameters");
            throw new HttpException(500, "Unknown error occurred when save");
        }

        return $this->handleRestView(204, "Updated successfully");
    }

    protected function deleteAction($id){
        if(empty($id)) throw new HttpException(400, "Missing parameter 'id'");

        $repo = $this->getRepository();

        $entity = $repo->findOneBy(array('id'=>$id));

        if(empty($entity)) throw new HttpException(404, "Not found");

        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleRestView(204, "Deleted successfully", array());
    }

    private function attributeToSetter($str) {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, "set_".$str);
    }
}