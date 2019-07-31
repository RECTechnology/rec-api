<?php

namespace App\FinancialApiBundle\Controller\Management\Admin;

use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\AbstractMethod;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LimitCount;
use App\FinancialApiBundle\Entity\LimitDefinition;
use App\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Financial\Currency;
use App\FinancialApiBundle\Entity\Category;

/**
 * Class CompaniesController
 * @package App\FinancialApiBundle\Controller\Manager
 */
class CompaniesController extends BaseApiController
{
    function getRepositoryName()
    {
        return "FinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }

    /**
     * @Rest\View
     * Permissions: ROLE_SUPER_ADMIN (all)
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function updateAction(Request $request, $id){
        //only the superadmin can access here
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $validParams = array(
            'cif',
            'email',
            'category',
            'type',
            'subtype',
            'offered_products',
            'needed_products',
            'description',
            'schedule',
            'prefix',
            'phone',
            'zip',
            'city',
            'country',
            'latitude',
            'longitude',
            'fixed_location',
            'address_number',
            'street',
            'street_type',
            'web',
            'neighborhood',
            'observations',
            'company_image',
            'public_image',
            'association'
        );

        $params = $request->request->all();
        foreach($params as $paramName=>$value){
            if(!in_array($paramName, $validParams)){
                throw new HttpException(400, 'Param ' . $paramName . ' can not be updated');
            }
        }

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository($this->getRepositoryName())->find($id);
        if(!$company) throw new HttpException(404, 'Group not found');


        $allowed_types = array('PRIVATE', 'COMPANY');
        if($request->request->has('type') && $request->request->get('type')!='') {
            $params['type'] = $request->request->get('type');
            if(in_array($params['type'], $allowed_types)) {
                $type = $params['type'];
            }
            else{
                throw new HttpException(400, "Invalid type");
            }
        }
        else{
            $type = $allowed_types[0];
        }

        $list_subtypes = array(
            'PRIVATE' => array('NORMAL', 'BMINCOME'),
            'COMPANY' => array('RETAILER', 'WHOLESALE')
        );
        $allowed_subtypes = $list_subtypes[$type];
        if($request->request->has('subtype') && $request->request->get('subtype')!='') {
            $params['subtype'] = $request->request->get('subtype');
            if(in_array($params['subtype'], $allowed_subtypes)) {
                $subtype = $params['subtype'];
            }
            else{
                throw new HttpException(400, "Invalid subtype");
            }
        }
        else{
            $subtype = $allowed_subtypes[0];
        }

        if(isset($params['category'])) {
            $category = $params['category'];
            $category_exists = $em->getRepository('FinancialApiBundle:Category')->findOneBy(array(
                    "id" => $category
                )
            );
            if (!$category_exists) {
                throw new HttpException(404, 'Category not found');
            } else {
                $company->setCategory($category_exists);
                $em->persist($company);
                $em->flush();
                $request->request->remove('category');
            }
        }
        try {
            $response = parent::updateAction($request, $id);
            if ($response->getStatusCode() == 204) {
                $em->persist($company);
                $em->flush();
            }
        } catch(LogicException $e){
            throw new HttpException(400, "Invalid params: " . $e->getMessage());
        }
        return $response;
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $groupsRepo = $this->getDoctrine()->getRepository($this->getRepositoryName());
        $id_group_root = $this->container->getParameter('id_group_root');

        if($id == $id_group_root ) throw new HttpException(405, 'Not allowed');

        $group = $groupsRepo->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        if(count($group->getusers()) > 0) throw new HttpException(405, 'Not allowed. Comapny with users');

        return parent::deleteAction($id);

    }

    private function _setMethods($methods, Group $group){

        $em = $this->getDoctrine()->getManager();

        $listMethods = $group->getMethodsList();

        $group->setMethodsList($methods);
        $em->persist($group);

        //get all fees and delete/create depending of methods

        $fees = $em->getRepository('FinancialApiBundle:ServiceFee')->findBy(array(
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
            $exchanges = $this->container->get('net.app.exchange_provider')->findAll();

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
        $limits = $em->getRepository('FinancialApiBundle:LimitDefinition')->findBy(array(
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
            $exchanges = $this->container->get('net.app.exchange_provider')->findAll();

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
        $limitCounts = $em->getRepository('FinancialApiBundle:LimitCount')->findBy(array(
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
            $methodConfig = $this->get('net.app.'.$methodExplode[1].'.'.$methodExplode[0].'.v1');
            $fee = $em->getRepository('FinancialApiBundle:ServiceFee')->findOneBy(array(
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

            $limit = $em->getRepository('FinancialApiBundle:LimitDefinition')->findOneBy(array(
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

            $limitCount = $em->getRepository('FinancialApiBundle:LimitCount')->findOneBy(array(
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
     */
    public function showAction($id){
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $group = $this->getRepository()->find($id);

        if(!$group) throw new HttpException(404,'Group not found');

        //TODO change this for tier metthods list
        $group = $group->getAdminView();
        $tier = $group->getTier();

        $methods = $this->get('net.app.method_provider')->findByTier($tier);

        $group->setAllowedMethods($methods);

        $limit_configuration = array();
        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine_mongodb')->getManager();
        foreach ($methods as $method){

            $tier_limit = $em->getRepository('FinancialApiBundle:TierLimit')->findOneBy(array(
                'method'    =>  $method->getCname().'-'.$method->getType(),
                'tier'  =>  $tier
            ));

            if(!$tier_limit) throw new HttpException(403, $method->getCname().'-'.$method->getType());
            $total_last_day = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 1);
            $total_last_month = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByMethod($group, $method, 30);

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
        $exchange_limits = $em->getRepository('FinancialApiBundle:TierLimit')->createQueryBuilder('e')
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

            $total_last_day = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $exchange_currency[1], 1);
            $total_last_month = $dm->getRepository('FinancialApiBundle:Transaction')->sumLastDaysByExchange($group, $exchange_currency[1], 30);

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
}
