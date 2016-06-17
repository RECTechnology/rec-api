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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class WalletController
 * @package Telepay\FinancialApiBundle\Controller\Management\User
 */
class WalletController extends RestApiController{

    /**
     * reads information about all wallets
     */
    public function read(){

        $user = $this->get('security.context')->getToken()->getUser();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

        //obtener los wallets
        $wallets = $userGroup->getWallets();

        //obtenemos la default currency
        $currency = $userGroup->getDefaultCurrency();

        $filtered = [];
        $available = 0;
        $balance = 0;
        $scale = 0;

        foreach($wallets as $wallet){
            $filtered[] = $wallet->getWalletView();
            $new_wallet = $this->exchange($wallet, $currency);
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

        //return $this->rest(201, "Account info got successfully", $filtered);
        return $this->restV2(200, "ok", "Wallet info got successfully", $filtered);

    }

    /**
     * read last 10 transactions
     */
    public function last(Request $request){

        $dm = $this->get('doctrine_mongodb')->getManager();

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userId = $user->getId();

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($userId == $this->container->getParameter('read_only_user_id')){
            $userId = $this->container->getParameter('chipchap_user_id');
        }

        $last10Trans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('group')->equals($userGroup->getId())
            ->limit(10)
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();

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

        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        //TODO quitar cuando haya algo mejor montado
        if($userId == $this->container->getParameter('read_only_user_id')){
            $userId = $this->container->getParameter('chipchap_user_id');
        }

        $last10Trans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
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
        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userId = $user->getId();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($userId == $this->container->getParameter('read_only_user_id')){
            $userId = $this->container->getParameter('chipchap_user_id');
        }

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){

            $query = $request->query->get('query');
            $search = $query['search'];
            if(isset($query['clients'])){
                $clients = json_decode($query['clients'], true);
            }
            $order = $query['order'];
            $dir = $query['dir'];
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

            $transactions = $qb
                ->field('group')->equals($userGroup->getId())
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->where("function() {
            if (typeof this.pay_in_info !== 'undefined') {
                if (typeof this.pay_in_info.amount !== 'undefined') {
                    if(String(this.pay_in_info.amount).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.pay_in_info.address !== 'undefined') {
                    if(String(this.pay_in_info.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.pay_in_info.status !== 'undefined') {
                    if(String(this.pay_in_info.status).indexOf('$search') > -1){
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

            }
            if (typeof this.pay_out_info !== 'undefined') {
                if (typeof this.pay_out_info.amount !== 'undefined') {
                    if(String(this.pay_out_info.amount).indexOf('$search') > -1){
                        return true;
                    }
                }
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
                if (typeof this.pay_out_info.pin !== 'undefined') {
                    if(String(this.pay_out_info.pin).indexOf('$search') > -1){
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
                if(typeof this.status !== 'undefined' && String(this.status).indexOf('$search') > -1){ return true;}
                if(typeof this.service !== 'undefined' && String(this.service).indexOf('$search') > -1){ return true;}
                if(typeof this.method !== 'undefined' && String(this.method).indexOf('$search') > -1){ return true;}
                if(typeof this.methodIn !== 'undefined' && String(this.methodIn).indexOf('$search') > -1){ return true;}
                if(typeof this.methodOut !== 'undefined' && String(this.methodOut).indexOf('$search') > -1){ return true;}
                if(String(this._id).indexOf('$search') > -1){ return true;}
                return false;
            }
            return true;
            }")
                ->sort('updated','desc')
                ->sort('id','desc')
                ->getQuery()
                ->execute();

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

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');

            $qb->field('group')->equals($userGroup->getId());

            if(isset($query['start_date'])){
                $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));
            }else{
                $fecha = new DateTime();
                $fecha->sub(new DateInterval('P3M'));
                $start_time = new \MongoDate($fecha->getTimestamp());
            }
            $qb->field('created')->gte($start_time);

            if(isset($query['finish_date'])){
                $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));
            }else{
                $finish_time = new \MongoDate();
            }
            $qb->field('created')->lte($finish_time);

            if(isset($query['status'])){
                if(!($query['status'] == 'all' || $query['status'] == "[]")){
                    $qb->field('status')->in(json_decode($query['status'], true));
                }
            }

            if(isset($query['methods'])){
                if(!($query['methods'] == 'all' || $query['methods'] == "[]")){
                    $qb->field('method')->in(json_decode($query['methods'], true));
                }
            }

            if(isset($query['pos'])){
                if(!($query['pos'] == 'all' || $query['pos'] == "[]")){
                    $qb->field('posId')->in(json_decode($query['pos'], true));
                }
            }

            if(isset($query['clients'])){
                if(!($query['clients'] == 'all' || $query['clients'] == "[]")){
                    $qb->field('client')->in(json_decode($query['clients'], true));
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

            $transactions = $qb
                ->sort('updated','desc')
                ->sort('id','desc')
                ->getQuery()
                ->execute();

        }else{
            $order = "updated";
            $dir = "desc";
            $transactions = $qb
                ->field('group')->equals($userGroup->getId())
                ->sort($order,$dir)
                ->getQuery()
                ->execute();
        }

        //Put client name
        $em = $this->getDoctrine()->getManager();
        $clientsInfo = $em->getRepository('TelepayFinancialApiBundle:Client')->findby(array('group' => $userGroup->getId()));
        $listClients = array();
        foreach($clientsInfo as $c){
            $listClients[$c->getId()]=$c->getName();
        }

        $entities = array_slice($transactions, $offset, $limit);
        $resArray = [];
        $cli_n = 0;
        foreach($entities->toArray() as $res){
            if($res->getClient()){
                $res->setClientData(
                    array(
                        "id" => $res->getClient(),
                        "name" => $listClients[$res->getClient()]
                    )
                );
                $cli_n++;
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
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities,
                'clients' => $cli_n
            )
        );
    }

    /**
     * return an array with the daily total amount of the filtered transactions
     */
    public function walletDailySumTransactions(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
            $userGroup = $user->getActiveGroup();
        }

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $services = $query['services'];
            if(isset($query['clients'])){
                $clients = json_decode($query['clients'], true);
            }
            $order = $query['order'];
            $dir = $query['dir'];
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

            $transactions = $qb
                ->field('group')->equals($userGroup->getId())
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->field('type')->notEqual('swift')
                ->where("function() {
            if (typeof this.dataIn !== 'undefined') {
                if (this.status != 'success') { return false;}
                if (typeof this.dataIn.phone_number !== 'undefined') {
                    if(String(this.dataIn.phone_number).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.address !== 'undefined') {
                    if(String(this.dataIn.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.reference !== 'undefined') {
                    if(String(this.dataIn.reference).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.pin !== 'undefined') {
                    if(String(this.dataIn.pin).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.order_id !== 'undefined') {
                    if(String(this.dataIn.order_id).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.previous_transaction !== 'undefined') {
                    if(String(this.dataIn.previous_transaction).indexOf('$search') > -1){
                        return true;
                    }
                }
            }
            if (typeof this.dataOut !== 'undefined') {
                if (typeof this.dataOut.transaction_pos_id !== 'undefined') {
                    if(String(this.dataOut.transaction_pos_id).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.halcashticket !== 'undefined') {
                    if(String(this.dataOut.halcashticket).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.txid !== 'undefined') {
                    if(String(this.dataOut.txid).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.address !== 'undefined') {
                    if(String(this.dataOut.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.id !== 'undefined') {
                    if(String(this.dataOut.id).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.reference !== 'undefined') {
                    if(String(this.dataOut.reference).indexOf('$search') > -1){
                        return true;
                    }
                }
            }
            if(JSON.parse(String('$services')).indexOf(String(this.service)) > -1) { return true;}
            if(typeof this.status !== 'undefined' && String(this.status).indexOf('$search') > -1){ return true;}
            if(typeof this.service !== 'undefined' && String(this.service).indexOf('$search') > -1){ return true;}
            if(String(this._id).indexOf('$search') > -1){ return true;}

            return false;
            }")
                ->sort($order,$dir)
                ->getQuery()
                ->execute();

        }else{
            $order = "id";
            $dir = "asc";

            $transactions = $qb
                ->field('group')->equals($userGroup->getId())
                ->sort($order,$dir)
                ->getQuery()
                ->execute();
        }

        $data = array();
        $scales = array();
        foreach($transactions->toArray() as $res){
            if($res->getStatus() == "success"){
                if(!array_key_exists($res->getCurrency(), $scales)){
                    $scales[$res->getCurrency()] = $res->getScale();
                }

                $created = $res->getCreated();
                if($created != "" && $created != null ){
                    $day = $created->format('Y') . "/" . $created->format('m') . "/" . $created->format('d');
                    if(!array_key_exists($day, $data)){
                        $data[$day] = array();
                    }
                    if(!isset($clients)) {
                        array_key_exists($res->getCurrency(), $data[$day])? $data[$day][$res->getCurrency()] += $res->getAmount():$data[$day][$res->getCurrency()] = $res->getAmount();
                    }
                    else{
                        if(in_array("0", $clients) || in_array($res->getClient(), $clients)){
                            array_key_exists($res->getCurrency(), $data[$day])? $data[$day][$res->getCurrency()] += $res->getAmount():$data[$day][$res->getCurrency()] = $res->getAmount();
                        }
                    }
                }
            }
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'daily' => $data,
                'scales' => $scales
            )
        );
    }

    /**
     * return transaction sum by day. week and month
     */
    public function benefits(Request $request){

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

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

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

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
     * return country benefits group by IP
     */
    public function countryBenefits(Request $request){

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

        $userId = $user->getId();
        $default_currency = $userGroup->getDefaultCurrency();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $result = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('group')->equals($userGroup->getId())
            ->field('status')->equals('success')
            ->field('type')->notEqual('swift')
            ->group(
                new \MongoCode('
                    function(trans){
                        return {
                            ip : trans.ip
                        };
                    }
                '),
                array(
                    'total'=>0
                )
            )
            ->reduce('
                function(curr, result){
                    result.total+=1;
                }
            ')
            ->getQuery()
            ->execute();

        $total = [];
        foreach($result->toArray() as $res){

            $json = file_get_contents('http://www.geoplugin.net/json.gp?ip='.$res['ip']);
            $data = json_decode($json);

            $changed  = false;
            foreach($total as $t){
                if(isset($t['name']) && $t['name'] == $data->geoplugin_countryName ){
                    $t['value'] = $t['value'] + $res['total'];
                    $changed = true;
                }
            }

            if($changed == false){
                $country['name'] = $data->geoplugin_countryName;
                $country['code'] = $data->geoplugin_countryCode;
                $country['flag'] = strtolower($data->geoplugin_countryCode);
                $country['value'] = $res['total'];
                $total[] = $country;
            }

        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            $total
        );
    }

    /**
     * send money between wallets
     */
    public function walletToWallet(Request $request, $currency){

        //sender user
        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //this name is the name of the group
        $parameters = array(
            'name',
            'amount',
            'concept'
        );

        $params = array();

        //Obtain the parameters
        foreach ($parameters as $param){
            if($request->request->has($param))
                $params[$param] = $request->request->get($param);
            else
                throw new HttpException(400,'Parameter '.$param.' not found');

        }

        //Search receiver user
        $em = $this->getDoctrine()->getManager();
        $receiver = $em->getRepository('TelepayFinancialApiBundle:Group')
            ->findOneBy(array('name' => $params['name']));

        if (!$receiver) throw new HttpException(404,'Receiver not found');

        //Check founds in sender wallet
        $sender_wallets = $userGroup->getWallets();
        $sender_wallet = null;
        foreach( $sender_wallets as $wallet){
            if( $wallet->getCurrency() == strtoupper($currency)){
                $sender_wallet = $wallet;
            }
        }

        if(!$sender_wallet) throw new HttpException(400,'Sender wallet not found');

        if($sender_wallet->getAvailable() < $params['amount']) throw new HttpException(401, 'Not founds enought');

        //obtaining receiver wallet
        $receiver_wallets = $receiver->getWallets();
        $receiver_wallet = null;
        foreach( $receiver_wallets as $wallet){
            if( $wallet->getCurrency() == strtoupper($currency)){
                $receiver_wallet = $wallet;
            }
        }

        if(!$receiver_wallet) throw new HttpException(400,'Receiver wallet not found');

        //Generate transactions and update wallets - without fees
        //SENDER TRANSACTION
        $sender_transaction = new Transaction();
        $sender_transaction->setStatus('success');
        $sender_transaction->setScale($sender_wallet->getScale());
        $sender_transaction->setCurrency($sender_wallet->getCurrency());
        $sender_transaction->setIp('');
        $sender_transaction->setVersion('');
        $sender_transaction->setService('transfer');
        $sender_transaction->setVariableFee(0);
        $sender_transaction->setFixedFee(0);
        $sender_transaction->setAmount($params['amount']);
        $sender_transaction->setDataIn(array(
            'description'   =>  'transfer->'.$currency,
            'concept'       =>  $params['concept']
        ));
        $sender_transaction->setDataOut(array(
            'sent_to'   =>  $receiver->getUsername(),
            'id_to'     =>  $receiver->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  strtoupper($currency)
        ));
        $sender_transaction->setTotal(-$params['amount']);
        $sender_transaction->setUser($user->getId());
        $sender_transaction->setGroup($userGroup->getId());


        $dm = $this->get('doctrine_mongodb')->getManager();

        $dm->persist($sender_transaction);
        $dm->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($user, -$params['amount'], $sender_transaction);

        //RECEIVER TRANSACTION
        $receiver_transaction = new Transaction();
        $receiver_transaction->setStatus('success');
        $receiver_transaction->setScale($sender_wallet->getScale());
        $receiver_transaction->setCurrency($sender_wallet->getCurrency());
        $receiver_transaction->setIp('');
        $receiver_transaction->setVersion('');
        $receiver_transaction->setService('transfer');
        $receiver_transaction->setVariableFee(0);
        $receiver_transaction->setFixedFee(0);
        $receiver_transaction->setAmount($params['amount']);
        $receiver_transaction->setDataOut(array(
            'received_from' =>  $user->getUsername(),
            'id_from'       =>  $user->getId(),
            'amount'        =>  $params['amount'],
            'currency'      =>  $receiver_wallet->getCurrency(),
            'previous_transaction'  =>  $sender_transaction->getId()
        ));
        $receiver_transaction->setDataIn(array(
            'sent_to'   =>  $receiver->getUsername(),
            'id_to'     =>  $receiver->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  strtoupper($currency),
            'description'   =>  'transfer->'.$currency,
            'concept'   =>  $params['concept']
        ));
        $receiver_transaction->setTotal($params['amount']);
        $receiver_transaction->setGroup($receiver->getId());

        $dm->persist($receiver_transaction);
        $dm->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($receiver, $params['amount'], $receiver_transaction);

        //todo update wallets
        $sender_wallet->setAvailable($sender_wallet->getAvailable() - $params['amount']);
        $sender_wallet->setBalance($sender_wallet->getBalance() - $params['amount']);

        $receiver_wallet->setAvailable($receiver_wallet->getAvailable() + $params['amount']);
        $receiver_wallet->setBalance($receiver_wallet->getBalance() + $params['amount']);

        $em->persist($sender_wallet);
        $em->persist($receiver_wallet);
        $em->flush();

        return $this->restV2(200, "ok", "Transaction got successfully");

    }

    /**
     * check user fees
     */
    public function userFees(Request $request){

        //get user
        $user = $this->get('security.context')->getToken()->getUser();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

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

        //get user
        $user = $this->get('security.context')->getToken()->getUser();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

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

    public function _exchange($amount, $curr_in, $curr_out){

        $dm = $this->getDoctrine()->getManager();
        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found');

        $price = $exchange->getPrice();

        $total = round($amount * $price,0);

        return $total;

    }

    public function _getBenefits($interval, $start = null, $end =null){

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
            $userGroup = $user->getActiveGroup();
        }

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
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $result = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('group')->equals($userGroup->getId())
            ->field('created')->gt($start_time)
            ->field('created')->lt($end_time)
            ->field('status')->equals('success')
            ->field('type')->notEqual('swift')
            ->group(
                new \MongoCode('
                    function(trans){
                        return {
                            currency : trans.currency
                        };
                    }
                '),
                array(
                    'total'=>0
                )
            )
            ->reduce('
                function(curr, result){
                    switch(curr.currency){
                        case "EUR":
                            if(curr.total){
                                result.total+=curr.total;
                            }
                            break;
                        case "MXN":
                            if(curr.total){
                                result.total+=curr.total;
                            }
                            break;
                        case "USD":
                            if(curr.total){
                                result.total+=curr.total;
                            }
                            break;
                        case "BTC":
                            if(curr.total){
                                result.total+=curr.total;
                            }
                            break;
                        case "FAC":
                            if(curr.total){
                                result.total+=curr.total;
                            }
                            break;
                        case "PLN":
                            if(curr.total){
                                result.total+=curr.total;
                            }
                            break;
                        case "":
                            if(curr.total){
                                result.total+=curr.total;
                            }
                            break;
                    }
                }
            ')
            ->getQuery()
            ->execute();

        $total = 0;
        //die(print_r($result,true));
        foreach($result->toArray() as $d){
            if($d['currency'] != ''){
                if($default_currency == $d['currency']){
                    $total = $total + $d['total'];
                }else{
                    $change = $this->_exchange($d['total'], $d['currency'], $default_currency);
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
     * makes an exchange between currencies in the wallet
     */
    public function exchange(UserWallet $wallet, $currency){

        $currency_actual = $wallet->getCurrency();
        if($currency_actual == $currency){
            $response['available'] = $wallet->getAvailable();
            $response['balance'] = $wallet->getBalance();
            $response['scale'] = $wallet->getScale();
            return $response;
        }
        $dm = $this->getDoctrine()->getManager();
        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$currency_actual,'dst'=>$currency),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found');

        $price = $exchange->getPrice();

        $response['available'] = round($wallet->getAvailable() * $price, 0);
        $response['balance'] = round($wallet->getBalance() * $price,0);
        $response['scale'] = null;

        return $response;

    }

    /**
     * makes an exchange between wallets
     */
    public function currencyExchange(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        if(!$userGroup) throw new HttpException(404, 'Group not found');

        //get params
        $paramNames = array(
            'amount',
            'currency_in',
            'currency_out'
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

        $currency_in = strtoupper($params['currency_in']);
        $currency_out = strtoupper($params['currency_out']);
        $service = 'exchange'.'_'.$currency_in.'to'.$currency_out;

        //getExchange
        $exchange = $this->_exchange($amount, $currency_in, $currency_out);

        if($exchange == 0) throw new HttpException(403, 'Amount must be bigger');

        //checkWallet sender
        $wallets = $userGroup->getWallets();

        $senderWallet = null;
        $receiverWallet = null;
        foreach($wallets as $wallet){
            if($params['currency_in'] == $wallet->getCurrency()){
                $senderWallet = $wallet;
            }elseif($params['currency_out'] == $wallet->getCurrency()){
                $receiverWallet = $wallet;
            }
        }

        if($senderWallet == null) throw new HttpException(404, 'Sender Wallet not found');
        if($receiverWallet == null) throw new HttpException(404, 'Receiver Wallet not found');

        if($amount > $senderWallet->getAvailable()) throw new HttpException(404, 'Not funds enough. ' . $amount . '>' . $senderWallet->getAvailable());

        //getFees
        $fees = $userGroup->getCommissions();

        $fixed_fee = null;
        $variable_fee = null;

        foreach($fees as $fee){
            if($fee->getServiceName() == $service){
                $fixed_fee = $fee->getFixed();
                $variable_fee = round((($fee->getVariable()/100) * $exchange), 0);
            }
        }

        $em = $this->getDoctrine()->getManager();

        $dm = $this->get('doctrine_mongodb')->getManager();
        //cashOut transaction
        $cashOut = Transaction::createFromRequest($request);
        $cashOut->setAmount($amount);
        $cashOut->setCurrency($currency_in);
        $cashOut->setDataIn($params);
        $cashOut->setFixedFee(0);
        $cashOut->setVariableFee(0);
        $cashOut->setTotal(-$params['amount']);
        $cashOut->setType('out');
        $cashOut->setMethod('exchange');
        $cashOut->setService($service);
        $cashOut->setUser($user->getId());
        $cashOut->setGroup($userGroup->getId());
        $cashOut->setVersion(1);
        $cashOut->setScale($senderWallet->getScale());
        $cashOut->setStatus('success');
        $cashOut->setDataIn($params);
        $cashOut->setDataOut(array(
            $currency_in =>  $amount,
            $currency_out=>     $exchange
        ));

        $dm->persist($cashOut);
        $dm->flush();

        $paramsOut = $params;
        $paramsOut['amount'] = $exchange;
        //cashIn transaction
        $cashIn = Transaction::createFromRequest($request);
        $cashIn->setAmount($exchange);
        $cashIn->setCurrency($currency_out);
        $cashIn->setDataIn($params);
        $cashIn->setFixedFee($fixed_fee);
        $cashIn->setVariableFee($variable_fee);
        $cashIn->setTotal($exchange);
        $cashIn->setService($service);
        $cashIn->setType('in');
        $cashIn->setMethod('exchange');
        $cashIn->setUser($user->getId());
        $cashIn->setGroup($userGroup->getId());
        $cashIn->setVersion(1);
        $cashIn->setScale($receiverWallet->getScale());
        $cashIn->setStatus('success');
        $cashIn->setDataIn($paramsOut);
        $cashIn->setDataOut(array(
            'previous_transaction'  =>  $cashOut->getId(),
            $currency_in    =>  $amount,
            $currency_out   =>  $exchange
        ));

        $dm->persist($cashIn);
        $dm->flush();

        //update wallets
        $senderWallet->setAvailable($senderWallet->getAvailable() - $amount);
        $senderWallet->setBalance($senderWallet->getBalance() - $amount);

        $receiverWallet->setAvailable($receiverWallet->getAvailable() + $exchange - $fixed_fee - $variable_fee);
        $receiverWallet->setBalance($receiverWallet->getBalance() + $exchange - $fixed_fee - $variable_fee);

        $em->persist($senderWallet);
        $em->persist($receiverWallet);
        $em->flush();

        //dealer
        $total_fee = $fixed_fee + $variable_fee;

        if( $total_fee != 0){
            //nueva transaccion restando la comision al user
            try{
                $this->_dealer($cashIn, $receiverWallet);
            }catch (HttpException $e){
                throw $e;
            }
        }

        //notification
        $this->container->get('notificator')->notificate($cashIn);

        //return
        return $this->restV2(200, "ok", "Exchange got successfully");

    }

    private function _dealer(Transaction $transaction, UserWallet $current_wallet){

        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $service_cname = $transaction->getService();

        $em = $this->getDoctrine()->getManager();

        $total_fee = round($transaction->getFixedFee() + $transaction->getVariableFee(),0);

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
        $userGroup = $user->getActiveGroup();

        $feeTransaction = Transaction::createFromTransaction($transaction);
        $feeTransaction->setAmount($total_fee);
        $feeTransaction->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'description'           =>  $service_cname.'->fee'
        ));
        $feeTransaction->setData(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'type'                  =>  'resta_fee'
        ));
        $feeTransaction->setDebugData(array(
            'previous_balance'  =>  $current_wallet->getBalance(),
            'previous_transaction'  =>  $transaction->getId()
        ));

        $feeTransaction->setTotal(-$total_fee);

        $feeTransaction->setType('fee');
        $feeTransaction->setMethod('exchange');

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $mongo->persist($feeTransaction);
        $mongo->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($userGroup, -$total_fee, $feeTransaction );

        //empezamos el reparto
        $creator = $userGroup->getGroupCreator();

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $dealer = $this->get('net.telepay.commons.fee_deal');
        $dealer->deal($creator, $amount, $service_cname, 'exchange', $currency, $total_fee, $transaction_id, $transaction->getVersion());

    }

}