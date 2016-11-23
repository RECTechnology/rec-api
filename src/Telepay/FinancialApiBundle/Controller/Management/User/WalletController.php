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
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        //obtener los wallets
        $wallets = $userGroup->getWallets();

        //obtenemos la default currency
        $currency = $userGroup->getDefaultCurrency();

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

        //return $this->rest(201, "Account info got successfully", $filtered);
        return $this->restV2(200, "ok", "Wallet info got successfully", $filtered);
    }

    /**
     * read last 10 transactions
     */
    public function last(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
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

        if($request->query->get('query')){
            $query = $request->query->get('query');
            $qb->field('group')->equals($userGroup->getId());

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
            $qb->field('group')->equals($userGroup->getId());
        }

        $transactions = $qb
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();

        $data = array();
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
                        if($trans_type == 'in' || $trans_type == 'out' || $trans_type == 'fee' || $trans_type == 'resta_fee') {
                            $balance[$currency] += $res->getTotal();
                        }

                        $updated = $res->getUpdated();
                        if($updated != "" && $updated != null ){
                            $day = $updated->format('Y') . "/" . $updated->format('m') . "/" . $updated->format('d');
                            if(!array_key_exists($day, $data)){
                                $data[$day] = array();
                            }
                            array_key_exists($res->getCurrency(), $data[$day])? $data[$day][$res->getCurrency()] += $res->getAmount():$data[$day][$res->getCurrency()] = $res->getAmount();
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

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'daily' => $data,
                'scales' => $scales,
                'balance' => $balance,
                'volume' => $volume,
                'elements' => $entities
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
     * return country benefits group by IP
     */
    public function countryBenefits(Request $request){
        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
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
        $sender_transaction->setMethod('wallet_to_wallet');
        $sender_transaction->setType('out');
        $sender_transaction->setVariableFee(0);
        $sender_transaction->setFixedFee(0);
        $sender_transaction->setAmount($params['amount']);
        $sender_transaction->setDataIn(array(
            'description'   =>  'transfer->'.$currency,
            'concept'       =>  $params['concept']
        ));
        $sender_transaction->setDataOut(array(
            'sent_to'   =>  $receiver->getName(),
            'id_to'     =>  $receiver->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  strtoupper($currency)
        ));
        $sender_transaction->setPayOutInfo(array(
            'beneficiary'   =>  $receiver->getName(),
            'beneficiary_id'     =>  $receiver->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  strtoupper($currency),
            'scale'     =>  Currency::$SCALE[strtoupper($currency)],
            'concept'       =>  $params['concept']
        ));
        $sender_transaction->setTotal(-$params['amount']);
        $sender_transaction->setUser($user->getId());
        $sender_transaction->setGroup($userGroup->getId());


        $dm = $this->get('doctrine_mongodb')->getManager();

        $dm->persist($sender_transaction);
        $dm->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($userGroup, -$params['amount'], $sender_transaction);

        //RECEIVER TRANSACTION
        $receiver_transaction = new Transaction();
        $receiver_transaction->setStatus('success');
        $receiver_transaction->setScale($sender_wallet->getScale());
        $receiver_transaction->setCurrency($sender_wallet->getCurrency());
        $receiver_transaction->setIp('');
        $receiver_transaction->setVersion('');
        $receiver_transaction->setService('transfer');
        $receiver_transaction->setMethod('wallet_to_wallet');
        $receiver_transaction->setType('in');
        $receiver_transaction->setVariableFee(0);
        $receiver_transaction->setFixedFee(0);
        $receiver_transaction->setAmount($params['amount']);
        $receiver_transaction->setDataOut(array(
            'received_from' =>  $userGroup->getName(),
            'id_from'       =>  $user->getId(),
            'amount'        =>  $params['amount'],
            'currency'      =>  $receiver_wallet->getCurrency(),
            'previous_transaction'  =>  $sender_transaction->getId()
        ));
        $receiver_transaction->setDataIn(array(
            'sent_to'   =>  $receiver->getName(),
            'id_to'     =>  $receiver->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  strtoupper($currency),
            'description'   =>  'transfer->'.$currency,
            'concept'   =>  $params['concept']
        ));
        $receiver_transaction->setPayInInfo(array(
            'sender'   =>  $receiver->getName(),
            'sender_id'     =>  $receiver->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  strtoupper($currency),
            'scale'  =>  Currency::$SCALE[strtoupper($currency)],
            'concept'   =>  $params['concept']
        ));
        $receiver_transaction->setTotal($params['amount']);
        $receiver_transaction->setGroup($receiver->getId());

        $dm->persist($receiver_transaction);
        $dm->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($receiver, $params['amount'], $receiver_transaction);

        //update wallets
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

//    public function _exchange($amount, $curr_in, $curr_out){
//
//        $dm = $this->getDoctrine()->getManager();
//        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
//        $exchange = $exchangeRepo->findOneBy(
//            array('src'=>$curr_in,'dst'=>$curr_out),
//            array('id'=>'DESC')
//        );
//
//        if(!$exchange) throw new HttpException(404,'Exchange not found');
//
//        $price = $exchange->getPrice();
//
//        $total = round($amount * $price,0);
//
//        return $total;
//
//    }

//    public function _getExchangePrice($amount, $curr_in, $curr_out){
//        $dm = $this->getDoctrine()->getManager();
//        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
//        $exchange = $exchangeRepo->findOneBy(
//            array('src'=>$curr_in,'dst'=>$curr_out),
//            array('id'=>'DESC')
//        );
//
//        if(!$exchange) throw new HttpException(404,'Exchange not found');
//
//        $price = $exchange->getPrice();
//
//        return $price;
//    }

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
        $exchanger = $this->container->get('net.telepay.commons.exchange_manipulator');
        foreach($result->toArray() as $d){
            if($d['currency'] != ''){
                if($default_currency == $d['currency']){
                    $total = $total + $d['total'];
                }else{
                    $change = $exchanger->exchange($d['total'], $d['currency'], $default_currency);
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
     * makes an exchange between currencies in the wallet. Called by currencyExchange
     */
//    public function exchange(UserWallet $wallet, $currency){
//
//        $currency_actual = $wallet->getCurrency();
//        if($currency_actual == $currency){
//            $response['available'] = $wallet->getAvailable();
//            $response['balance'] = $wallet->getBalance();
//            $response['scale'] = $wallet->getScale();
//            return $response;
//        }
//        $dm = $this->getDoctrine()->getManager();
//        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
//        $exchange = $exchangeRepo->findOneBy(
//            array('src'=>$currency_actual,'dst'=>$currency),
//            array('id'=>'DESC')
//        );
//
//        if(!$exchange) throw new HttpException(404,'Exchange not found');
//
//        $price = $exchange->getPrice();
//
//        $response['available'] = round($wallet->getAvailable() * $price, 0);
//        $response['balance'] = round($wallet->getBalance() * $price,0);
//        $response['scale'] = null;
//
//        return $response;
//
//    }

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

        $from = strtoupper($params['from']);
        $to = strtoupper($params['to']);
        $service = 'exchange'.'_'.$from.'to'.$to;

        //check if method is available
        $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $from.'to'.$to,
            'type'      =>  'exchange'
        ));

        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Exchange temporally unavailable');

        //check group exchange limits
        $limit = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findOneBy(array(
            'cname'     =>  $service,
            'group'     => $userGroup->getId()
        ));
        if($limit->getDay()==0)throw new HttpException(403, 'Exchange temporally unavailable');

        $exchanger = $this->container->get('net.telepay.commons.exchange_manipulator');
        $exchanger->doExchange($amount, $from, $to, $userGroup, $user);

        //return
        return $this->restV2(200, "ok", "Exchange got successfully");

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