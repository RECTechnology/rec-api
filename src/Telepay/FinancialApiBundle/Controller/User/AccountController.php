<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\User;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends RestApiController{

    /**
     * @Rest\View
     */
    public function read(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $user->setAllowedServices($user->getAllowedServices());
        $user->setAccessToken(null);
        $user->setRefreshToken(null);
        $user->setAuthCode(null);
        return $this->handleRestView(200, "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function analytics(Request $request){

        if($request->query->has('start_time') && is_int($request->query->get('start_time')))
            $start_time = new \MongoDate($request->query->get('start_time'));
        else $start_time = new \MongoDate(time()-31*24*3600); // 1 month ago

        if($request->query->has('end_time') && is_int($request->query->get('end_time')))
            $end_time = new \MongoDate($request->query->get('end_time'));
        else $end_time = new \MongoDate(); // now

        $interval = 'day';

        $env = true;

        $jsAssocs = array(
            'day' => 'getDay()'
        );

        if(!array_key_exists($interval, $jsAssocs))
            throw new HttpException(400, "Bad interval");

        $dm = $this->get('doctrine_mongodb')->getManager();

        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $result = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('mode')->equals($env)
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->group(
                new \MongoCode('
                    function(trans){
                        return {
                            '.$interval.': trans.timeIn.'.$jsAssocs[$interval].'
                        };
                    }
                '),
                array('success' => 0, 'fail' => 0)
            )
            ->reduce('
                function(curr, result){
                    if(curr.successful)
                        result.success++;
                    else
                        result.fail++;
                }
            ')
            ->getQuery()
            ->execute();

        return $this->handleRestView(
            200,
            "Request successful",
            array(
                'total'=>$result->getCommandResult()['count'],
                'elements'=>$result->toArray()
            )
        );
    }


}