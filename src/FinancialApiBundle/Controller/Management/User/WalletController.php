<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace App\FinancialApiBundle\Controller\Management\User;

use App\FinancialApiBundle\Entity\Tier;
use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\DependencyInjection\App\Commons\LimitAdder;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Financial\Currency;

/**
 * Class WalletController
 * @package App\FinancialApiBundle\Controller\Management\User
 */
class WalletController extends RestApiController{

    /**
     * read list of commerces with exchange available
     */
    public function listCommerce(Request $request){

        $total = 0;
        $all = array();
        $em = $this->getDoctrine()->getManager();
        $kyc2_id = $em->getRepository(Tier::class)->findOneBy(array('code'  =>  'KYC2'));
        $where = array(
            'type'  =>  'COMPANY',
            'level'  =>  $kyc2_id->getId(),
            'active'  =>  1
        );
        $list_companies = $em->getRepository('FinancialApiBundle:Group')->findBy($where);

        foreach ($list_companies as $company) {
            ++$total;
            $all[] = array(
                'id' => $company->getId(),
                'name' => $company->getName(),
                'kyc' => $company->getLevel()->getCode(),
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
        $userGroup = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        $last10Trans = $dm->getRepository('FinancialApiBundle:Transaction')->last10Transactions($userGroup);

        $resArray = [];
        foreach($last10Trans->toArray() as $res){
            $resArray [] = $res;
        }

        return $this->restV2(200, "ok", "Last 10 transactions got successfully", $resArray);
    }

    /**
     * reads transactions by day
     */
    public function walletDayTransactions(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction');

        if($request->query->has('day') && $request->query->get('day')!=''){
            $day = $request->query->get('day');
            $start_time = new \MongoDate(strtotime(date($day . ' 00:00:00')));
            $finish_time = new \MongoDate(strtotime(date($day . ' 23:59:59')));
        }else{
            throw new HttpException(400, "Incorrect day");
        }

        $transactions = $dm->getRepository('FinancialApiBundle:Transaction')->findTransactions($userGroup, $start_time, $finish_time, '', 'id', 'desc');
        $in = 0;
        $out = 0;
        $total = 0;
        foreach($transactions->toArray() as $res){
            if(!$res->getDeleted() && !$res->getInternal()) {
                $amount = $res->getTotal();
                if ($amount > 0) {
                    $in += $amount;
                } else {
                    $out += $amount;
                }
                $total += $amount;
            }
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

        $balance = array();
        $volume = array();

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $userGroup = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction');

        if($request->query->get('query')){
            $query = $request->query->get('query');
            $qb->field('group')->equals($userGroup->getId());
            $qb->field('internal')->equals(false);
            $qb->field('deleted')->equals(false);

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
            $qb->field('deleted')->equals(false);
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
            $clientsInfo = $em->getRepository('FinancialApiBundle:Client')->findby(array('group' => $userGroup->getId()));
            $listClients = array();
            foreach($clientsInfo as $c){
                $listClients[$c->getId()]=$c->getName();
            }

            $resArray = [];
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
     * sends money to another user
     */
    public function send(){

    }

    /**
     * pays to a commerce integrated with app
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

}