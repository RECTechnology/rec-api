<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 9:11 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Notifications;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotificationsController extends FOSRestController{

    public function notify(Request $request){

        static $paramNames=array(
            'UTID'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            $params[]=$request->get($paramName, 'null');
        }

        $dm = $this->get('doctrine_mongodb')->getManager();

        $consulta = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals(1)
            ->getQuery()->execute();

        $consulta->toArray();
        print_r($consulta);

        //Response
        $resp = new ApiResponseBuilder(
            200,
            "Bravo",
            $consulta
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }
}