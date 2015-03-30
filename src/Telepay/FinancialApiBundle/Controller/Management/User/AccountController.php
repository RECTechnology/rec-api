<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Proxies\__CG__\Telepay\FinancialApiBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function read(Request $request){
        //die(print_r(get_class($this->get('security.token_storage')->getToken()), true));
        $user = $this->get('security.context')->getToken()->getUser();
        $user->setAllowedServices(
            $this->get('net.telepay.service_provider')->findByRoles($user->getRoles())
        );
        return $this->restV2(200,"ok", "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id=null){

        $user = $this->get('security.context')->getToken()->getUser();
        $id=$user->getId();

        if($request->request->has('password')){
            if($request->request->has('repassword')){
                $userManager = $this->container->get('access_key.security.user_provider');
                $user = $userManager->loadUserById($id);
                $user->setPlainPassword($request->get('password'));
                $userManager->updatePassword($user);
                $request->request->remove('password');
                $request->request->remove('repassword');
            }else{
                throw new HttpException(404,'Parameter repassword not found');
            }

        }

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function speed(Request $request){
        $end_time = new \MongoDate();
        $start_time = new \MongoDate($end_time->sec-3600);

        $dm = $this->get('doctrine_mongodb')->getManager();

        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $last1hTrans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('mode')->equals(true)
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->field('successful')->equals(true)
            ->count()
            ->getQuery()
            ->execute();
        return $this->restV2(
            200,"ok", "Last hour speed got successfully", $last1hTrans
        );
    }

    /**
     * @Rest\View
     */
    public function analytics(Request $request){

        if($request->query->has('start_time') && is_int($request->query->get('start_time')))
            $start_time = new \MongoDate($request->query->get('start_time'));
        else $start_time = new \MongoDate(strtotime(date('Y-m-01 00:00:00'))); // 1th of month

        if($request->query->has('end_time') && is_int($request->query->get('end_time')))
            $end_time = new \MongoDate($request->query->get('end_time'));
        else $end_time = new \MongoDate(strtotime(date('Y-m-01 00:00:00'))+31*24*3600); // 1th of next month

        $interval = 'day';

        $env = true;

        $jsAssocs = array(
            'day' => 'getDate()'
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
                array(
                    's1'=>0,
                    's2'=>0,
                    's3'=>0,
                    's4'=>0,
                    's5'=>0,
                    's6'=>0,
                    's7'=>0,
                    's8'=>0,
                    's9'=>0,
                    's10'=>0,
                    's11'=>0,
                    's12'=>0,
                    's13'=>0
                )
            )
            ->reduce('
                function(curr, result){
                    if(curr.successful)
                        switch(curr.service){
                            case 1:
                                result.s1++;
                                break;
                            case 2:
                                result.s2++;
                                break;
                            case 3:
                                result.s3++;
                                break;
                            case 4:
                                result.s4++;
                                break;
                            case 5:
                                result.s5++;
                                break;
                            case 6:
                                result.s6++;
                                break;
                            case 7:
                                result.s7++;
                                break;
                            case 8:
                                result.s8++;
                                break;
                            case 9:
                                result.s9++;
                                break;
                            case 10:
                                result.s10++;
                                break;
                            case 11:
                                result.s11++;
                                break;
                            case 12:
                                result.s12++;
                                break;
                            case 13:
                                result.s13++;
                                break;
                        }
                }
            ')
            ->getQuery()
            ->execute();

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total'=>$result->getCommandResult()['count'],
                'elements'=>$result->toArray()
            )
        );
    }

    /**
     * @Rest\View
     */
    public function updateCurrency(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        if($request->request->has('currency'))
            $currency=$request->request->get('currency');
        else
            throw new HttpException(404,'currency not found');

        $em=$this->getDoctrine()->getManager();

        $user->setDefaultCurrency(strtoupper($currency));

        $em->persist($user);
        $em->flush();

        return $this->restV2(200,"ok", "Account info got successfully", $user);
    }


    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:User";
    }

    function getNewEntity()
    {
        return new User();
    }
}