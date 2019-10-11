<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\Entity\ProductKind;
use App\FinancialApiBundle\Entity\GroupProduct;
use App\FinancialApiBundle\Controller\BaseApiController;

class ProductController extends BaseApiController{

    function getRepositoryName()
    {
        return "FinancialApiBundle:GroupProduct";
    }

    function getNewEntity()
    {
        return new GroupProduct();
    }

    /**
     * @Rest\View
     */
    public function listAllAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository('FinancialApiBundle:Category')->findAll();
        return $this->restV2(200, 'ok', 'Request successfull', $categories);
    }

    /**
     * @Rest\View
     */
    public function indexAction(Request $request){
        $account = $this->getUser()->getActiveGroup();
        $procucts = array(
            'offered'=> $account->getOfferedProducts(),
            'needed'=> $account->getNeededProducts()
        );
        return $this->restV2(200, 'ok', 'Request successfull', $procucts);
    }

    public function setAction(Request $request){
        $user = $this->getUser();
        $group = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $saved = false;
        $result = array();
        if($request->query->has('offered_products') && $request->query->get('offered_products')!='') {
            $offered_products = $request->query->get('offered_products');
            if(strlen($offered_products)>200){
                throw new HttpException(400, "Offered products too large");
            }
            $group->setOfferedProducts($offered_products);
            $result['offered_products'] = $offered_products;
            $saved = true;
        }
        if($request->query->has('needed_products') && $request->query->get('needed_products')!='') {
            $needed_products = $request->query->get('needed_products');
            if(strlen($needed_products)>200){
                throw new HttpException(400, "Needed products too large");
            }
            $group->setNeededProducts($needed_products);
            $result['needed_products'] = $needed_products;
            $saved = true;
        }
        if($saved){
            $em->persist($group);
            $em->flush();
            return $this->restV2(200, 'ok', 'Request successfull', $result);
        }
        else{
            throw new HttpException(400, "ProductKind list empty");
        }
    }


    public function setCategoryAction(Request $request){
        $user = $this->getUser();
        $group = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        if($request->query->has('category') && $request->query->get('category')!='') {
            $category_id = $request->query->get('category');
            $category = $em->getRepository('FinancialApiBundle:Category')->findOneBy(array(
                    'id' => $category_id
                )
            );
            if($category){
                $group->setCategory($category);
                $em->persist($group);
                $em->flush();
            }
            else{
                throw new HttpException(400, "New category do not exists");
            }
        }
        else{
            throw new HttpException(400, "New category empty");
        }
        return $this->restV2(200, 'ok', 'Request successfull', $group->getCategory());
    }
}