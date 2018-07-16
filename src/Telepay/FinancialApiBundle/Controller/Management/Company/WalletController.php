<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class WalletController
 * @package Telepay\FinancialApiBundle\Controller\Management\Company
 */
class WalletController extends RestApiController {

    /**
     * @Rest\View
     * description: add comment to transaction
     */
    public function updateAction(Request $request,$id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($trans->getGroup());

        $user = $this->getUser();
        if(!$user->hasGroup($company->getName())) throw new HttpException(403, 'You don\'t have the necessary permissions in this company');
        //TODO check if is granted role worker
        if($request->request->has('comment') && $request->request->get('comment') != ''){
            $comment = $trans->getComment();
            $comment[] = $request->request->get('comment');
            $trans->setComment($comment);

        }else{
            throw new HttpException(404, 'No valid params found');
        }

        $dm->flush();

        return $this->restV2(204,"ok", "Updated");
    }

    /**
     * reads information about all wallets for a company provided
     * permissions: all in this company allowed
     */
    public function read($company_id){
        $user = $this->getUser();
        $company = $this->getDoctrine()->getManager()->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);

        if(!$company) throw new HttpException(404,'Account with id '. $company_id.' not found');
        //check permissions
        if(!$user->hasGroup($company->getName()))
            throw new HttpException('You don\'t have the necessary permissions');

        //obtener los wallets
        $wallets = $company->getWallets();

        //obtenemos la default currency
        $currency = $company->getDefaultCurrency();

        $filtered = [];
        $available = 0;
        $balance = 0;
        $scale = 0;
        $exchanger = $this->container->get('net.telepay.commons.exchange_manipulator');

        foreach($wallets as $wallet){
            $filtered[] = $wallet->getWalletView();
            if($company->getPremium()){
                if($wallet->getCurrency() == Currency::$FAC){
                    $wallet->setCurrency('FAIRP');
                }elseif($currency == Currency::$FAC){
                    $currency = 'FAIRP';
                }
            }
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
     * reads transactions by wallets
     */
    public function walletTransactions(Request $request, $company_id){

        $company = $this->getDoctrine()->getManager()->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);
        $user = $this->getUser();
        if(!$company) throw new HttpException(404, 'Company not found');
        if(!$user->hasGroup($company->getName())) throw new HttpException(403, 'You don\'t have the necessary permissions');

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query')){
            $query = $request->query->get('query');
            $qb->field('group')->equals($company->getId());

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
            $qb->field('group')->equals($company->getId());
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
            $clientsInfo = $em->getRepository('TelepayFinancialApiBundle:Client')->findby(array('group' => $company->getId()));
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
     * makes an exchange between wallets for a provided company
     */
    public function currencyExchange(Request $request, $company_id){
        $em = $this->getDoctrine()->getManager();
        if(!$this->get('security.context')->isGranted('ROLE_WORKER'))
            throw new HttpException(403, 'You don\' have the necessary permissions');

        $user = $this->getUser();
        //TODO check client to find the group for control from api
        $userGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);

        if(!$userGroup) throw new HttpException(404, 'Group not found');
        if(!$user->hasGroup($userGroup->getName()))
            throw new HttpException(403,'You don\' have the necessary permissions');

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
        $exchange = $exchanger->doExchange($amount, $from, $to, $userGroup, $user);

        //return
        return $this->restV2(200, "ok", "Exchange got successfully", $exchange);

    }

    /**
     * index fees
     */
    public function indexFees(Request $request, $account_id){
        //get group
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($account_id);

        if(!$company) throw new HttpException(404, 'Company not found');


        //getFees
        $fees = $company->getCommissions();

        //get methods by tier
        $methods = $this->get('net.telepay.method_provider')->findByTier($company->getTier());
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
     * show fees
     */
    public function showFees(Request $request, $account_id, $method){
        //get group
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($account_id);

        if(!$company) throw new HttpException(404, 'Company not found');

        //getFees
        $fee = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findOneBy(array(
            'service_name'  =>  $method,
            'group' =>  $company
        ));

        if(!$fee) throw new HttpException(404, 'Service method not found');

        //get methods by tier
        $methods = $this->get('net.telepay.method_provider')->findByTier($company->getTier());

        if(!in_array($fee->getServiceName(), $methods) && strpos($fee->getServiceName(), 'exchange') != 0) throw new HttpException(403, 'Method not active for your account');

        $currency = $fee->getCurrency();
        $fee->setScale($currency);

        return $this->restV2(200, "ok", "Fees info got successfully", $fee);

    }
}
