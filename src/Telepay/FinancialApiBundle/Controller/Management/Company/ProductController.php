<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpFoundation\File\File;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\Product;
use Telepay\FinancialApiBundle\Entity\GroupProduct;

class ProductController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function listAllAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $procucts = $em->getRepository('TelepayFinancialApiBundle:Product')->findAll();
        return $this->restV2(200, 'ok', 'Request successfull', $procucts);
    }

    /**
     * @Rest\View
     */
    public function indexAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $group = $user->getActiveGroup();
        $procucts = $em->getRepository('TelepayFinancialApiBundle:GroupProduct')->find(array(
            'group' => $group
        ));
        $list = array();
        foreach($procucts as $product){
            $list[] = $product->getProduct();
        }
        return $this->restV2(200, 'ok', 'Request successfull', $procucts);
    }

    public function addAction(Request $request){
        $user = $this->getUser();
        $group = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        if($request->query->has('products') && $request->query->get('products')!='') {
            $list_ids = json_decode($request->query->get('products'));
            foreach($list_ids as $prod_id){
                $procuct = $em->getRepository('TelepayFinancialApiBundle:Product')->findOneBy(array(
                        'product' => $prod_id
                    )
                );
                if($procuct){
                    $procuct_group = $em->getRepository('TelepayFinancialApiBundle:GroupProduct')->findOneBy(array(
                        'group' => $group,
                        'product' => $procuct
                    ));
                    if(!$procuct_group){
                        $new = new GroupProduct();
                        $new->setGroup($group);
                        $new->setProduct($procuct);
                        $em->persist($new);
                        $em->flush();
                    }
                }
            }
        }
        else{
            throw new HttpException(400, "Product list empty");
        }
    }


    public function deleteAction(Request $request){
        $user = $this->getUser();
        $group = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        if($request->query->has('products') && $request->query->get('products')!='') {
            $list_ids = json_decode($request->query->get('products'));
            foreach($list_ids as $prod_id){
                $procuct = $em->getRepository('TelepayFinancialApiBundle:Product')->findOneBy(array(
                        'product' => $prod_id
                    )
                );
                if($procuct){
                    $procuct_group = $em->getRepository('TelepayFinancialApiBundle:GroupProduct')->findOneBy(array(
                        'group' => $group,
                        'product' => $procuct
                    ));
                    if($procuct_group){
                        $em->remove($procuct_group);
                        $em->flush();
                    }
                }
            }
        }
        else{
            throw new HttpException(400, "Product list empty");
        }
    }
}