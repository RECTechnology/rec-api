<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Commerce;

use Telepay\FinancialApiBundle\Entity\POS;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class POSController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function indexAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $all = $this->getRepository()->findBy(array(
            'user'  =>  $user
        ));

        $total = count($all);

        $filtered = [];
        foreach($all as $tpv){
            $filtered[] = $tpv->getTpvView();
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $filtered
            )
        );
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $request->request->add(array(
            'user'   =>  $user
        ));

        $currency = $request->request->get('currency');
        $type = $request->request->get('type');
        if(!$type) {
            if(strtoupper($currency) == 'BTC'){
                $type = strtoupper($currency);
            }else{
                $type = 'PNP';
            }
        }
        $request->request->remove('currency');
        $request->request->remove('type');
        $request->request->add(array(
            'currency'  => strtoupper($currency),
            'type'      =>  strtoupper($type)
        ));

        $request->request->add(array(
            'pos_id'    =>  uniqid(),
            'cname'     =>  'POS-'.$type
        ));

        return parent::createAction($request);

    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id=null){

        if($request->request->has('cname')) throw new HttpException(400, "Parameter cname can't be changed");

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);

    }

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:POS";
    }

    function getNewEntity()
    {
        return new POS();
    }


}