<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ResellerDealer;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class CompaniesController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class CompaniesController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }

    /**
     * @Rest\View
     * Permissions: ROLE_SUPER_ADMIN (all)
     */
    public function updateAction(Request $request, $id){

        $em = $this->getDoctrine()->getManager();

        $company = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$company) throw new HttpException(404, 'Group not found');

        $methods = null;
        if($request->request->has('methods_list')){
            $tier = $company->getTier();
            $methods = $request->get('methods_list');
            $request->request->remove('methods_list');
        }

        $group = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        $creator_company = null;
        if($request->request->has('creator_company')){
            $creator_company = $request->request->get('creator_company');
            $request->request->remove('creator_company');
        }

        $response = parent::updateAction($request, $id);

        if($response->getStatusCode() == 204){
            if($methods !== null){
                $this->_setMethods($methods, $company);
            }

            if($creator_company != null){
                //change creaotor company
                $new_creator = $em->getRepository($this->getRepositoryName())->find($creator_company);

                $company->setGroupCreator($new_creator);

                $em->persist($company);
                $em->flush();
            }
        }

        return $response;

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){

        $groupsRepo = $this->getDoctrine()->getRepository($this->getRepositoryName());

        $default_group = $this->container->getParameter('id_group_default');
        $level0_group = $this->container->getParameter('id_group_level_0');
        $id_group_root = $this->container->getParameter('id_group_root');

        if($id == $default_group || $id == $level0_group || $id == $id_group_root ) throw new HttpException(405, 'Not allowed');

        $group = $groupsRepo->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        if(count($group->getusers()) > 0) throw new HttpException(403, 'Not allowed. Comapny with users');

        return parent::deleteAction($id);

    }

    private function _setMethods($methods, Group $group){

        $em = $this->getDoctrine()->getManager();

        $listMethods = $group->getMethodsList();

        $group->setMethodsList($methods);
        $em->persist($group);

        //get all fees and delete/create depending of methods

        $fees = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findBy(array(
            'group'  =>  $group->getId()
        ));

        $exchangeFees = array();
        foreach($fees as $fee){
            $cnameExplode = explode('_', $fee->getServiceName());
            if($cnameExplode[0] != 'exchange'){
                if(!in_array($fee->getServiceName(),$methods)){
                    $em->remove($fee);
                }
            }else{
                if(in_array($fee->getServiceName(), $exchangeFees)){
                    $em->remove($fee);
                }else{
                    $exchangeFees[] = $fee->getServiceName();
                }

            }

        }

        if(count($exchangeFees) == 0){
            //create exchange fees if not exists
            $exchanges = $this->container->get('net.telepay.exchange_provider')->findAll();

            foreach($exchanges as $exchange){
                //create fee for this group

                $fee = new ServiceFee();
                $fee->setFixed(0);
                $fee->setVariable(0);
                $fee->setCurrency($exchange->getCurrencyOut());
                $fee->setServiceName('exchange_'.$exchange->getCname());
                $fee->setGroup($group);

                $em->persist($fee);
                $em->flush();

            }
        }

        //get all limits and delete/create depending of methods
        $em = $this->getDoctrine()->getManager();
        $limits = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findBy(array(
            'group'  =>  $group->getId()
        ));

        $exchangeLimits = array();
        foreach($limits as $limit){
            $cnameExplode = explode('_', $limit->getCname());
            if($cnameExplode[0] != 'exchange'){
                if(!in_array($limit->getCname(),$methods)){
                    $em->remove($limit);
                }
            }else{
                if(in_array($limit->getCname(), $exchangeLimits)){
                    $em->remove($limit);
                }else{
                    $exchangeLimits[] = $limit->getCname();
                }

            }
        }

        if(count($exchangeLimits) == 0){
            //create exchange limits if not exists
            $exchanges = $this->container->get('net.telepay.exchange_provider')->findAll();

            foreach($exchanges as $exchange){
                //create limit for this group
                $limit = new LimitDefinition();
                $limit->setDay(0);
                $limit->setWeek(0);
                $limit->setMonth(0);
                $limit->setYear(0);
                $limit->setTotal(0);
                $limit->setSingle(0);
                $limit->setCname('exchange_'.$exchange->getCname());
                $limit->setCurrency($exchange->getCurrencyOut());
                $limit->setGroup($group);

                $em->persist($limit);
                $em->flush();

            }
        }

        //get all limitCount and delete/create depending of methods
        $em = $this->getDoctrine()->getManager();
        $limitCounts = $em->getRepository('TelepayFinancialApiBundle:LimitCount')->findBy(array(
            'group'  =>  $group->getId()
        ));

        $exchangeLimitCounts = array();
        foreach($limitCounts as $limitCount){
            $cnameExplode = explode('_', $limitCount->getCname());
            if($cnameExplode[0] != 'exchange'){
                if(!in_array($limitCount->getCname(),$methods)){
                    $em->remove($limitCount);
                }
            }else{
                if(in_array($limitCount->getCname(), $exchangeLimitCounts)){
                    $em->remove($limitCount);
                }else{
                    $exchangeLimitCounts[] = $limitCount->getCname();
                }

            }
        }

        //add new fees limits limitCounts for this methods
        foreach($methods as $method){

            $methodExplode = explode('-',$method);
            //get method config
            $methodConfig = $this->get('net.telepay.'.$methodExplode[1].'.'.$methodExplode[0].'.v1');
            $fee = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findOneBy(array(
                'group'  =>  $group->getId(),
                'service_name'  =>  $method
            ));

            if(!$fee){
                //create new ServiceFee
                $newFee = new ServiceFee();
                $newFee->setGroup($group);
                $newFee->setFixed(0);
                $newFee->setVariable(0);
                $newFee->setServiceName($method);
                $newFee->setCurrency($methodConfig->getCurrency());

                $em->persist($newFee);
            }

            $limit = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findOneBy(array(
                'group'  =>  $group->getId(),
                'cname'  =>  $method
            ));

            if(!$limit){
                //create new LimitDefinition
                $newLimit = new LimitDefinition();
                $newLimit->setGroup($group);
                $newLimit->setCurrency($methodConfig->getCurrency());
                $newLimit->setCname($method);
                $newLimit->setDay(0);
                $newLimit->setWeek(0);
                $newLimit->setMonth(0);
                $newLimit->setYear(0);
                $newLimit->setSingle(0);
                $newLimit->setTotal(0);

                $em->persist($newLimit);
            }

            $limitCount = $em->getRepository('TelepayFinancialApiBundle:LimitCount')->findOneBy(array(
                'group'  =>  $group->getId(),
                'cname'  =>  $method
            ));

            if(!$limitCount){
                //create new LimitCount
                $newCount = new LimitCount();
                $newCount->setDay(0);
                $newCount->setWeek(0);
                $newCount->setMonth(0);
                $newCount->setYear(0);
                $newCount->setSingle(0);
                $newCount->setTotal(0);
                $newCount->setCname($method);
                $newCount->setGroup($group);

                $em->persist($newCount);
            }
        }


        $em->flush();

        return $this->rest(204, "Edited");
    }

    /**
     * @Rest\View
     * Permissions: ROLE_SUPER_ADMIN (all)
     */
    public function showResellerFees(Request $request, $id){

        //search company
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository($this->getRepositoryName())->find($id);


        //get all Resellers
        $resellers = $em->getRepository('TelepayFinancialApiBundle:ResellerDealer')->findBy(array(
            'company_origin'    =>  $company
        ));

        return $this->restV2(200, 'success', 'reseller info got sucessfully', $resellers);

    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        $user = $this->get('security.context')->getToken()->getUser();

        $group = $this->getRepository()->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        //TODO change this for tier metthods list
        $group = $group->getAdminView();
        $tier = $group->getTier();

        $methods = $this->get('net.telepay.method_provider')->findByTier($tier);

        $group->setAllowedMethods($methods);

        $limit_configuration = array();
        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine_mongodb')->getManager();
        foreach ($methods as $method){

            $tier_limit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                'method'    =>  $method->getCname().'-'.$method->getType(),
                'tier'  =>  $tier
            ));

            if(!$tier_limit) throw new HttpException('403', $method->getCname().'-'.$method->getType());
            $total_last_day = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 1);
            $total_last_month = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 30);

            $lim = array(
                'method'    =>  $method,
                'month_limit'     =>  $tier_limit->getMonth(),
                'month_spent'   =>  $total_last_month[0]['total'] ? $total_last_month[0]['total']:0,
                'day_limit' =>  $tier_limit->getDay(),
                'day_spent' =>  $total_last_day[0]['total'] ? $total_last_day[0]['total']:0
            );

            $limit_configuration[] = $lim;
        }

        //TODO search exchange methods by tier
        $exchange_limits = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->createQueryBuilder('e')
            ->where('e.tier = :tier')
            ->andWhere('e.method LIKE :method')
            ->setParameter('tier', $tier)
            ->setParameter('method', 'exchange%')
            ->getQuery()
            ->getResult();

        foreach ($exchange_limits as $exchange_limit){

            $exchange_currency = explode('_', $exchange_limit->getMethod());
            $exchange_method = new AbstractMethod(
                $exchange_limit->getMethod(),
                strtolower($exchange_limit->getMethod()),
                'exchange',
                $exchange_currency[1],
                false,
                '',
                '',
                $tier
            );

            $total_last_day = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $exchange_currency[1], 1);
            $total_last_month = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $exchange_currency[1], 30);

            $lim = array(
                'method'    =>  $exchange_method,
                'month_limit'     =>  $exchange_limit->getMonth(),
                'month_spent'   =>  $total_last_month[0]['total'] ? $total_last_month[0]['total']:0,
                'day_limit' =>  $exchange_limit->getDay(),
                'day_spent' =>  $total_last_day[0]['total'] ? $total_last_day[0]['total']:0
            );

            $limit_configuration[] = $lim;

        }

        $group->setLimitConfiguration($limit_configuration);

        $fees = $group->getCommissions();
        foreach ( $fees as $fee ){
            $currency = $fee->getCurrency();
            $fee->setScale($currency);
        }
        $limits = $group->getLimits();
        foreach ( $limits as $lim ){
            $currency = $lim->getCurrency();
            $lim->setScale($currency);
        }
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => 1,
                'start' => 0,
                'end' => 1,
                'elements' => $group
            )
        );
    }

    /**
     * @Rest\View
     */
    public function addReseller(Request $request, $id){

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($id);

        if(!$company) throw new HttpException(404, 'Company not found');

        $paramNames = array(
            'company_reseller',
            'fee',
            'method'
        );

        $params = array();
        foreach ($paramNames as $paramName){
            if(!$request->request->has($paramName)) throw new HttpException(404, 'Param '.$paramName.' not found');
            $params[$paramName] = $request->request->get($paramName);
        }

        $company_reseller = $em->getRepository($this->getRepositoryName())->find($params['company_reseller']);
        if(!$company_reseller) throw new HttpException(404, 'Company reseller not found');

        $pos = strpos($params['method'], 'exchange');
        //TODO if is exchange
//        die(print_r(strpos($params['method'], 'exchange'),true));
        //exchange must be sent like exchange_currency
        if($pos!== false){
            $explodeMethod = explode( '_',$params['method']);
//            die(print_r($explodeMethod,true));
            $currency = strtoupper($explodeMethod[1]);
            $currencies = Currency::$ALL;
            foreach ($currencies as $cur){
                if($cur != $currency){
                    //TODO check if reseller exists
                    $resellerExist = $em->getRepository('TelepayFinancialApiBundle:ResellerDealer')->findOneBy(array(
                        'method'    =>  'exchange_'.$cur.'to'.$currency,
                        'company_origin'    =>  $company,
                        'company_reseller'  =>  $company_reseller
                    ));

                    if(!$resellerExist){
                        //TODO check valid method
                        //TODO create reseller
                        $reseller = new ResellerDealer();
                        $reseller->setMethod('exchange_'.$cur.'to'.$currency);
                        $reseller->setFee($params['fee']);
                        $reseller->setCompanyReseller($company_reseller);
                        $reseller->setCompanyOrigin($company);

                        $em->persist($reseller);
                        $em->flush();
                    }
                }
            }


        }else{
            //TODO check if reseller exists
            $resellerExist = $em->getRepository('TelepayFinancialApiBundle:ResellerDealer')->findOneBy(array(
                'method'    =>  $params['method'],
                'company_origin'    =>  $company,
                'company_reseller'  =>  $company_reseller
            ));

            if($resellerExist) throw new HttpException(409, 'Duplicate resource');

            //TODO check valid method
            //TODO create reseller
            $reseller = new ResellerDealer();
            $reseller->setMethod($params['method']);
            $reseller->setFee($params['fee']);
            $reseller->setCompanyReseller($company_reseller);
            $reseller->setCompanyOrigin($company);

            $em->persist($reseller);
            $em->flush();

        }


        return $this->restV2(201, 'success', 'Reseller created successfully', $reseller);
    }

    /**
     * @Rest\View
     */
    public function editReseller(Request $request, $id, $reseller_dealer){

        $em = $this->getDoctrine()->getManager();
        $reseller = $em->getRepository('TelepayFinancialApiBundle:ResellerDealer')->find($reseller_dealer);

        if(!$reseller) throw new HttpException(404, 'Reseller dealer not found');
        if(!$request->request->has('fee')){
            throw new HttpException(404, 'Param fee not found');
        }else{
            $reseller->setFee($request->request->get('fee'));
            $em->flush();
        }

        return $this->restV2(204, 'success', 'Reseller fee updated successfully', $reseller);

    }

}
