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

        $userGroup = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();

        $all = $this->getRepository()->findBy(array(
            'group'  =>  $userGroup
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
        $userGroup = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        $pos = $this->getRepository()->findOneBy(array(
            'id'  =>  $id,
            'group'  =>  $userGroup
        ));
        if(empty($pos)) throw new HttpException(404, "Not found");
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function linkPOS($id){
        $pos = $this->getRepository()->findOneBy(array(
            'linking_code'  =>  $id
        ));
        if(empty($pos)) throw new HttpException(404, "Not found");

        $pos->setLinked(true);
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $company = $pos->getGroup();

        $response = array(
            'pos'   =>  $pos,
            'company'   =>  $company
        );

        return $this->restV2(200, 'Success','POS linked successfully', $response);

    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        if(!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) throw new HttpException(403, 'You don\' have the necessary permissions');

        $request->request->add(array(
            'group'   =>  $userGroup
        ));

        $currency = ($request->request->has('currency') && $request->request->get('currency')!='')?$request->request->get('currency'):'EUR';
        $type = $request->request->get('type');
        if(!$request->request->has('type')) {
            if(strtoupper($currency) == 'BTC' || strtoupper($currency) == 'FAC') {
                $type = strtoupper($currency);
            }else{
                throw new HttpException(400, "Bad request, parameter 'type' not found");
            }
        }
        $request->request->remove('currency');
        $request->request->remove('type');

        if(!$request->request->has('active')){
            $request->request->add(array(
                'active'     =>  true
            ));
        }

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
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) throw new HttpException(403, 'You don\' have the necessary permissions');
        $pos = $this->getRepository()->findOneBy(array(
            'id'  =>  $id,
            'group'  =>  $userGroup
        ));
        if(empty($pos)) throw new HttpException(404, "Not found");
        if($request->request->has('cname')) throw new HttpException(400, "Parameter cname can't be changed");
        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) throw new HttpException(403, 'You don\' have the necessary permissions');
        $pos = $this->getRepository()->findOneBy(array(
            'id'  =>  $id,
            'group'  =>  $userGroup
        ));
        if(empty($pos)) throw new HttpException(404, "Not found");
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