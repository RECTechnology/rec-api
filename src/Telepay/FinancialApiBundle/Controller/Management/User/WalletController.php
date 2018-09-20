<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class WalletController
 * @package Telepay\FinancialApiBundle\Controller\Management\User
 */
class WalletController extends RestApiController{

    /**
     * reads information about all wallets
     * DEPRECATED
     */
    public function read(){
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //obtener los wallets
        $wallets = $userGroup->getWallets();
        $currency = 'REC';

        $filtered = [];
        $available = 0;
        $balance = 0;
        $scale = 0;
        $exchanger = $this->container->get('net.telepay.commons.exchange_manipulator');

        foreach($wallets as $wallet){
            $filtered[] = $wallet->getWalletView();
            $new_wallet = $exchanger->exchangeWallet($wallet, $currency);
            $available = round($available + $new_wallet['available'],0);
            $balance = round($balance + $new_wallet['balance'],0);
            if($new_wallet['scale'] != null) $scale = $new_wallet['scale'];
        }

        //montamos el wallet
        $multidivisa = [];
        $multidivisa['id'] = 'multidivisa';
        $multidivisa['currency'] = $currency;
        $multidivisa['available'] = $available;
        $multidivisa['balance'] = $balance;
        $multidivisa['scale'] = $scale;
        $filtered[] = $multidivisa;

        return $this->restV2(200, "ok", "Wallet info got successfully", $filtered);
    }

    /**
     * read list of commerces with exchange available
     */
    public function listCommerce(Request $request){
        $total = 0;
        $all = array();
        $em = $this->getDoctrine()->getManager();
        $where = array(
            'type'  =>  'COMPANY',
            'tier'  =>  1
        );
        $list_companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy($where);

        foreach ($list_companies as $company) {
            $total += 1;
            $all[] = array(
                'id' => $company->getId(),
                'name' => $company->getName(),
                'company_image' => $company->getCompanyImage()
            );
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all
            )
        );
    }

    /**
     * read last 10 transactions
     */
    public function last(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $last10Trans = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->last10Transactions($userGroup);

        $resArray = [];
        foreach($last10Trans->toArray() as $res){
            $resArray [] = $res;
        }

        return $this->restV2(200, "ok", "Last 10 transactions got successfully", $resArray);
    }

    /**
     * read single transaction
     */
    public function single(Request $request, $id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $last10Trans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('group')->equals($userGroup->getId())
            ->field('id')->equals($id)
            ->limit(1)
            ->getQuery()
            ->execute();

        $resArray = [];
        foreach($last10Trans->toArray() as $res){
            $resArray [] = $res;
        }

        return $this->restV2(200, "ok", "Single transaction got successfully", $resArray);
    }

    /**
     * reads transactions by wallets
     */
    public function walletTransactions(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){

            $query = $request->query->get('query');
            $search = $query['search'];
            if(isset($query['clients'])){
                $clients = json_decode($query['clients'], true);
            }
            if(isset($query['start_date'])){
                $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));//date('Y-m-d 00:00:00')
            }else{
                $fecha = new DateTime();
                $fecha->sub(new DateInterval('P3M'));
                $start_time = new \MongoDate($fecha->getTimestamp());
            }

            if(isset($query['finish_date'])){
                $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));
            }else{
                $finish_time = new \MongoDate();
            }

            $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findTransactions($userGroup, $start_time, $finish_time, $search, 'id', 'desc');

        }else{
            $order = "updated";
            $dir = "desc";
            $transactions = $qb
                ->field('group')->equals($userGroup->getId())
                ->sort($order,$dir)
                ->getQuery()
                ->execute();
        }


        $em = $this->getDoctrine()->getManager();
        $clientsInfo = $em->getRepository('TelepayFinancialApiBundle:Client')->findby(array('group' => $userGroup->getId()));
        $listClients = array();
        foreach($clientsInfo as $c){
            $listClients[$c->getId()]=$c->getName();
        }

        $resArray = [];
        foreach($transactions->toArray() as $res){
            if($res->getClient()){
                $res->setClientData(
                    array(
                        "id" => $res->getClient(),
                        "name" => $listClients[$res->getClient()]
                    )
                );
            }

            if(!isset($clients)) {
                $resArray [] = $res;
            }
            else{
                if(in_array("0", $clients) || in_array($res->getClient(), $clients)){
                    $resArray []= $res;
                }
            }
        }
        $total = count($resArray);
        $entities = array_slice($resArray, $offset, $limit);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities
            )
        );
    }

    /**
     * reads transactions by day
     */
    public function walletDayTransactions(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->has('day') && $request->query->get('day')!=''){
            $day = $request->query->get('day');
            $start_time = new \MongoDate(strtotime(date($day . ' 00:00:00')));
            $finish_time = new \MongoDate(strtotime(date($day . ' 23:59:59')));
        }else{
            throw new HttpException(400, "Incorrect day");
        }

        $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findTransactions($userGroup, $start_time, $finish_time, '', 'id', 'desc');
        $in = 0;
        $out = 0;
        $total = 0;
        foreach($transactions->toArray() as $res){
            $amount = $res->getTotal();
            if($amount > 0){
                $in += $amount;
            }
            else{
                $out += $amount;
            }
            $total += $amount;
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'in' => $in,
                'out' => $out
            )
        );
    }

    /**
     * reads transactions by wallets
     */
    public function walletTransactionsV2(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query')){
            $query = $request->query->get('query');
            $qb->field('group')->equals($userGroup->getId());
            $qb->field('internal')->equals(false);

            if(isset($query['start_date'])){
                $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));
            }else{
                $fecha = new DateTime();
                $fecha->sub(new DateInterval('P3M'));
                $start_time = new \MongoDate($fecha->getTimestamp());
            }
            $qb->field('updated')->gte($start_time);

            if(isset($query['finish_date'])){
                $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));
            }else{
                $finish_time = new \MongoDate();
            }
            $qb->field('updated')->lte($finish_time);

            if(isset($query['status'])){
                if(!($query['status'] == 'all')){
                    if(count($query['status']) == 0){
                        $qb->field('status')->in(array(), true);
                    }
                    else{
                        $qb->field('status')->in($query['status']);
                    }
                }
            }

            if(isset($query['clients'])){
                if(!($query['clients'] == 'all')){
                    if(count($query['clients']) == 0){
                        $qb->field('client')->in(array(), true);
                    }
                    else{
                        $qb->field('client')->in($query['clients']);
                    }
                }
            }

            if(isset($query['search'])) {
                if ($query['search'] != '') {
                    $search = $query['search'];
                    $qb->where("function() {
                    if (typeof this.pay_in_info !== 'undefined') {
                        if (typeof this.pay_in_info.address !== 'undefined') {
                            if(String(this.pay_in_info.address).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_in_info.concept !== 'undefined') {
                            if(String(this.pay_in_info.concept).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_in_info.reference !== 'undefined') {
                            if(String(this.pay_in_info.reference).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_in_info.txid !== 'undefined') {
                            if(String(this.pay_in_info.txid).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_in_info.teleingreso_id !== 'undefined') {
                            if(String(this.pay_in_info.teleingreso_id).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_in_info.charge_id !== 'undefined') {
                            if(String(this.pay_in_info.charge_id).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_in_info.track !== 'undefined') {
                            if(String(this.pay_in_info.track).indexOf('$search') > -1){
                                return true;
                            }
                        }
                    }
                    if (typeof this.pay_out_info !== 'undefined') {
                        if (typeof this.pay_out_info.halcashticket !== 'undefined') {
                            if(String(this.pay_out_info.halcashticket).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_out_info.txid !== 'undefined') {
                            if(String(this.pay_out_info.txid).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_out_info.address !== 'undefined') {
                            if(String(this.pay_out_info.address).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_out_info.concept !== 'undefined') {
                            if(String(this.pay_out_info.concept).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_out_info.email !== 'undefined') {
                            if(String(this.pay_out_info.email).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_out_info.find_token !== 'undefined') {
                            if(String(this.pay_out_info.find_token).indexOf('$search') > -1){
                                return true;
                            }
                        }
                        if (typeof this.pay_out_info.phone !== 'undefined') {
                            if(String(this.pay_out_info.phone).indexOf('$search') > -1){
                                return true;
                            }
                        }
                    }
                    if (typeof this.dataIn !== 'undefined') {
                        if (typeof this.dataIn.previous_transaction !== 'undefined') {
                            if(String(this.dataIn.previous_transaction).indexOf('$search') > -1){
                                return true;
                            }
                        }
                    }
                    if ('$search') {
                        if(String(this._id).indexOf('$search') > -1){ return true;}
                        return false;
                    }
                    return true;
                    }"
                    );
                }
            }
        }
        else{
            $qb->field('internal')->equals(false);
            $qb->field('group')->equals($userGroup->getId());
        }

        $transactions = $qb
            ->field('status')->notIn(array('deleted'))
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();

        $data = array();
        $dataCustom = array();
        $scales = array();
        if($request->query->get('query')){
            $all_pos = false;
            if(isset($query['pos'])){
                if(($query['pos'] == 'all')){
                    $all_pos = true;
                }
            }
            else{
                $query['pos'] = array();
            }

            $all_in = false;
            if(isset($query['methods_in'])){
                if(($query['methods_in'] == 'all')){
                    $all_in = true;
                }
            }
            else{
                $query['methods_in'] = array();
            }

            $all_out = false;
            if(isset($query['methods_out'])){
                if(($query['methods_out'] == 'all')){
                    $all_out = true;
                }
            }
            else{
                $query['methods_out'] = array();
            }

            $all_swift_in = false;
            if(isset($query['swift_in'])){
                if(($query['swift_in'] == 'all')){
                    $all_swift_in = true;
                }
            }
            else{
                $query['swift_in'] = array();
            }

            $all_swift_out = false;
            if(isset($query['swift_out'])){
                if(($query['swift_out'] == 'all')){
                    $all_swift_out = true;
                }
            }
            else{
                $query['swift_out'] = array();
            }

            $all_exchange = false;
            if(isset($query['exchanges'])){
                if(($query['exchanges'] == 'all')){
                    $all_exchange = true;
                }
            }
            else{
                $query['exchanges'] = array();
            }

            $fees = false;
            if(isset($query['fees'])){
                if(($query['fees'] == '1')){
                    $fees = true;
                }
            }

            $em = $this->getDoctrine()->getManager();
            $clientsInfo = $em->getRepository('TelepayFinancialApiBundle:Client')->findby(array('group' => $userGroup->getId()));
            $listClients = array();
            foreach($clientsInfo as $c){
                $listClients[$c->getId()]=$c->getName();
            }

            $resArray = [];
            $balance = array();
            $volume = array();
            foreach($transactions->toArray() as $res){
                if($res->getClient() && isset($listClients[$res->getClient()])){
                    $res->setClientData(
                        array(
                            "id" => $res->getClient(),
                            "name" => $listClients[$res->getClient()]
                        )
                    );
                }

                $filtered = false;
                if($res->getPosId()){
                    if($all_pos || in_array($res->getPosId(), $query['pos'])){
                        $filtered = true;
                    }
                }
                elseif($res->getType() == 'in'){
                    $method = $res->getMethod();
                    if(strpos($method,'exchange')===false){
                        if($all_in || in_array($method, $query['methods_in'])){
                            $filtered = true;
                        }
                    }
                    else{
                        if($all_exchange || in_array($method, $query['exchanges'])){
                            $filtered = true;
                        }
                    }
                }
                elseif($res->getType() == 'out'){
                    $method = $res->getMethod();
                    if(strpos($method,'exchange')===false){
                        if($all_out || in_array($method, $query['methods_out'])){
                            $filtered = true;
                        }
                    }
                    else{
                        if($all_exchange || in_array($method, $query['exchanges'])){
                            $filtered = true;
                        }
                    }
                }
                elseif($res->getType() == 'exchange'){
                    if($all_exchange || in_array($res->getMethod(), $query['exchanges'])){
                        $filtered = true;
                    }

                }
                elseif($res->getType() == 'swift'){
                    if($all_swift_in || $all_swift_out || in_array($res->getMethodIn(), $query['swift_in']) || in_array($res->getMethodOut(), $query['swift_out'])){
                        $filtered = true;
                    }
                }
                elseif($res->getType() == 'fee' || $res->getType() == 'resta_fee'){
                    if($fees){
                        $method = $res->getMethod();
                        if(strpos($method,'exchange')===false){
                            if(strpos($method,'-in')>0){
                                $method = substr($method, 0, -3);
                                if($all_in || in_array($method, $query['methods_in'])){
                                    $filtered = true;
                                }
                            }
                            elseif(strpos($method,'-out')>0){
                                $method = substr($method, 0, -4);
                                if($all_out || in_array($method, $query['methods_out'])){
                                    $filtered = true;
                                }
                            }
                            else{
                                $list_methods = explode("-", $method);
                                $method_in = $list_methods[0];
                                $method_out = $list_methods[1];
                                if($all_swift_in || $all_swift_out || in_array($method_in, $query['swift_in']) || in_array($method_out, $query['swift_out'])){
                                    $filtered = true;
                                }
                            }
                        }
                        else{
                            if($all_exchange || in_array($method, $query['exchanges'])){
                                $filtered = true;
                            }
                        }
                    }
                }

                if($filtered) {
                    $resArray [] = $res;
                    if($res->getStatus() == "success"){
                        if(!array_key_exists($res->getCurrency(), $scales)){
                            $currency = $res->getCurrency();
                            $scales[$currency] = $res->getScale();
                            $volume[$currency] = 0;
                            $balance[$currency] = 0;
                        }

                        $volume[$currency]+=$res->getAmount();
                        $trans_type = $res->getType();
                        if($trans_type == 'in' || $trans_type == 'out' || $trans_type == 'fee' || $trans_type == 'resta_fee' || $trans_type == 'exchange') {
                            $balance[$currency] += $res->getTotal();
                        }

                        $updated = $res->getUpdated();
                        if($updated != "" && $updated != null ){
                            $day = $updated->format('Y') . "/" . $updated->format('m') . "/" . $updated->format('d');
                            if(!array_key_exists($day, $data)){
                                $data[$day] = array();
                                $dataCustom[$day] = array();
                            }

                            if(array_key_exists($res->getCurrency(), $data[$day])){

                                $data[$day][$res->getCurrency()] += $res->getAmount();
                                if($res->getType() == 'in'){
                                    $dataCustom[$day][$res->getCurrency()]['in'] += $res->getAmount();
                                }elseif ($res->getType() == 'out'){
                                    $dataCustom[$day][$res->getCurrency()]['out'] += $res->getAmount();
                                }elseif($res->getType() == 'fee' || $res->getType() == 'exchange'){
                                    if($res->getTotal() > 0){
                                        $dataCustom[$day][$res->getCurrency()]['in'] += $res->getAmount();
                                    }else{
                                        $dataCustom[$day][$res->getCurrency()]['out'] += $res->getAmount();
                                    }
                                }else{

                                }
                                $dataCustom[$day][$res->getCurrency()]['volume'] += $res->getAmount();

                            }else{
                                $data[$day][$res->getCurrency()] = $res->getAmount();
                                $in = 0;
                                $out = 0;
                                if($res->getType() == 'in'){
                                    $in = $res->getAmount();
                                }elseif($res->getType() == 'out'){
                                    $out = $res->getAmount();
                                }elseif($res->getType() == 'fee' || $res->getType() == 'exchange'){
                                    if($res->getTotal() > 0){
                                        $in += $res->getAmount();
                                    }else{
                                        $out += $res->getAmount();
                                    }
                                }

                                $temp = array(
                                    'in'    =>  $in,
                                    'out'   =>  $out,
                                    'volume'    =>  $res->getAmount()
                                );
                                $dataCustom[$day][$res->getCurrency()] = $temp;
                            }
//                            array_key_exists($res->getCurrency(), $data[$day])? $data[$day][$res->getCurrency()] += $res->getAmount():$data[$day][$res->getCurrency()] = $res->getAmount();
                        }
                    }
                }
            }
        }
        else{
            $resArray = $transactions->toArray();
        }

        $total = count($resArray);
        $entities = array_slice($resArray, $offset, $limit);
        $response = array();
        foreach ($entities as $entity){
            $entity->setComment($entity->getComment());
            $response[] = $entity;
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'daily' => $data,
                'daily_custom'  =>  $dataCustom,
                'scales' => $scales,
                'balance' => $balance,
                'volume' => $volume,
                'elements' => $response
            )
        );
    }

    /**
     * return transaction sum by day. week and month
     */
    public function benefits(Request $request){
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $default_currency = $userGroup->getDefaultCurrency();
        $day = $this->_getBenefits('day');
        $week = $this->_getBenefits('week');
        $month = $this->_getBenefits('month');

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'day'       =>  $day,
                'week'      =>  $week,
                'month'     =>  $month,
                'currency'  =>  $default_currency,
                'scale'     =>  Currency::$SCALE[$default_currency]
            )
        );
    }

    /**
     * return transaction sum by month (last 12 months)
     */
    public function monthBenefits(Request $request){
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $default_currency = $userGroup->getDefaultCurrency();
        $day1 = date('Y-m-1 00:00:00');
        $monthly = [];

        for($i = 0; $i < 12; $i++){
            $actual_month = strtotime("-".$i." month", strtotime($day1));
            $next_month = $actual_month + 31 * 24 * 3600;
            $start_time = strtotime(date('Y-m-d', $actual_month));
            $end_time = strtotime(date('Y-m-d', $next_month));
            $month = $this->_getBenefits('month', $start_time,$end_time);
            $strmonth = date('Y-m', $actual_month);
            $monthly[$strmonth] = $month;

        }

        $monthly['currency'] = $default_currency;

        $monthly['scale'] = Currency::$SCALE[$default_currency];

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            $monthly
        );
    }

    /**
     * check user fees
     */
    public function userFees(Request $request){
        //get group
        $group = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        //getFees
        $fees = $group->getCommissions();

        //return only active methods
        $methods = $group->getMethodsList();
        $activeFees = [];

        foreach ( $fees as $fee){
            //return only allowed methods
            if(in_array($fee->getServiceName(), $methods)  || strpos($fee->getServiceName(), 'exchange') == 0){
                $currency = $fee->getCurrency();
                $fee->setScale($currency);
                $activeFees [] = $fee;
            }

        }

        return $this->restV2(200, "ok", "Fees info got successfully", $activeFees);

    }

    /**
     * check user limits
     */
    public function userLimits(Request $request){
        //get group
        $group = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        //getLimits
        $limits = $group->getLimits();

        //return only active methods
        $methods = $group->getMethodsList();
        $activeLimits = [];

        foreach ( $limits as $limit){
            if(in_array($limit->getCname(), $methods)){
                $currency = $limit->getCurrency();
                $limit->setScale($currency);
                $activeLimits [] = $limit;
            }

        }

        return $this->restV2(200, "ok", "Fees info got successfully", $activeLimits);

    }

    public function _getBenefits($interval, $start = null, $end =null){
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $default_currency = $userGroup->getDefaultCurrency();

        switch($interval){
            case 'day':
                $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00'))); // 00:00
                $end_time = new \MongoDate(strtotime(date('Y-m-d 23:59:59'))); // 23:59
                break;
            case 'month':
                if($start==null||$end== null){
                    $start_time = new \MongoDate(strtotime(date('Y-m-01 00:00:00'))); // 1th of month
                    $end_time = new \MongoDate(strtotime(date('Y-m-01 00:00:00'))+31*24*3600); // 1th of next month
                }else{
                    $start_time = new \MongoDate($start); // 1th of month
                    $end_time = new \MongoDate($end); // 1th of next month
                }
                break;
            case 'week':
                $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00',strtotime('last monday')))); // Monday
                $end_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00',strtotime('next monday')))); // Sunday
                break;
            default:
                $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00'))); // 00:00
                $end_time = new \MongoDate(strtotime(date('Y-m-d 23:59:59'))); // 23:59
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $result = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->getBenefits($userGroup, $start_time, $end_time);

        $total = 0;
        //die(print_r($result,true));
        $exchanger = $this->container->get('net.telepay.commons.exchange_manipulator');
        foreach($result->toArray() as $d){
            if($d['currency'] != ''){
                if($default_currency == $d['currency']){
                    $total = $total + $d['total'];
                }else{
                    if($d['currency'] == Currency::$FAC && $userGroup->getPremium() == true){
                        $currency = 'FAIRP';
                    }else{
                        $currency = $d['currency'];
                    }
                    $change = $exchanger->exchange($d['total'], $currency, $default_currency);
                    $total = $total + $change;
                }

            }
        }

        return $total;

    }

    /**
     * sends money to another user
     */
    public function send(){

    }

    /**
     * pays to a commerce integrated with telepay
     */
    public function pay(){

    }

    /**
     * receives money from other users
     */
    public function receive(){

    }

    /**
     * recharges the wallet with any integrated payment method
     */
    public function cashIn(){

    }

    /**
     * sends cash from the wallet to outside
     */
    public function cashOut(){

    }

    /**
     * makes an exchange between wallets
     */
    public function currencyExchange(Request $request){
        $em = $this->getDoctrine()->getManager();
        if(!$this->get('security.context')->isGranted('ROLE_WORKER')) throw new HttpException(403, 'You don\' have the necessary permissions');

        $user = $this->get('security.context')->getToken()->getUser();
        //TODO check client to find the group for control from api
        $userGroup = $user->getActiveGroup();

        if(!$userGroup) throw new HttpException(404, 'Group not found');

        //get params
        $paramNames = array(
            'amount',
            'from',
            'to'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Parameter "'.$paramName.'" not found');
            }
        }

        $amount = floor($params['amount']);

        if($amount<=0){
            throw new HttpException(403, 'Amount must be greater than 0.');
        }

        $from = strtoupper($params['from']);
        $to = strtoupper($params['to']);
        $method = 'exchange'.'_'.$from.'to'.$to;

        //check if method is available
        $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $from.'to'.$to,
            'type'      =>  'exchange'
        ));

        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Exchange temporally unavailable');

        if($userGroup->getPremium() == true && $userGroup->getTier() < 1){
            throw new HttpException(403, 'You must promote to BankOfTheCommons user to do it.');
        }

        $exchanger = $this->container->get('net.telepay.commons.exchange_manipulator');
        $exchanger->doExchange($amount, $from, $to, $userGroup, $user);

        //return
        return $this->restV2(200, "ok", "Exchange went successfully");

    }

    public function getPdfReceipt(Request $request, $id){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getGroup() != $userGroup->getId()) throw new HttpException('You don\'t have the necessary permissions');

        if($transaction->getMethod() != 'sepa' && $transaction->getMethod() != 'transfer') throw new HttpException('Bad method');

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());

        $paymentInfo = $transaction->getPayOutInfo();
        $body = array(
            'transaction'   =>  $transaction,
            'user'  =>  $user,
            'company'   =>  $company,
            'iban'  =>  $paymentInfo['iban'],
            'swift' =>  $paymentInfo['bic_swift'],
            'beneficiary'   =>  $paymentInfo['beneficiary'],
            'concept'   =>  $paymentInfo['concept']
        );

        $html = $this->container->get('templating')->render('TelepayFinancialApiBundle:Email:receipt.html.twig', $body);


        $dompdf = $this->container->get('slik_dompdf');
        $dompdf->getpdf($html);
        $pdfoutput = $dompdf->output();

        $filename = $transaction->getMethod().'_'.$transaction->getId().'.pdf';

        $response = new Response();

        //set headers
        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);

        $response->setContent($pdfoutput);
        return $response;


    }

}