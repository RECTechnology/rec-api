<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use App\FinancialApiBundle\Controller\Management\Admin\TransactionsController;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Financial\Currency;

/**
 * Class WalletController
 * @package App\FinancialApiBundle\Controller\Management\Company
 */
class WalletController extends RestApiController {

    /**
     * @Rest\View
     * description: add comment to transaction
     */
    public function updateAction(Request $request,$id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($trans->getGroup());

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
     * reads transactions by wallets
     */
    public function walletTransactions(Request $request, $company_id){

        $company = $this->getDoctrine()->getManager()->getRepository('FinancialApiBundle:Group')->find($company_id);
        $user = $this->getUser();
        if(!$company) throw new HttpException(404, 'Company not found');
        $userGroup = $user->getActiveGroup();

        $adminRoles = $this->getDoctrine()->getRepository("FinancialApiBundle:UserGroup")->findOneBy(array(
                'user'  =>  $user->getId(),
                'group' =>  $userGroup->getId()
            )
        );

        if($company->getId() == $userGroup->getId()){
            if(!$adminRoles->hasRole('ROLE_ADMIN') || !$user->hasGroup($userGroup->getName()))
                throw new HttpException(409, 'You don\'t have the necesary permissions in this company');
        }else{
            if(!$adminRoles->hasRole('ROLE_SUPER_ADMIN'))
                throw new HttpException(409, 'You don\'t have the necesary permissions');
        }

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction');

        if($request->query->get('query')){
            $query = json_decode($request->query->get('query'), true);
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
                        if (typeof this.pay_in_info.txid !== 'undefined') {
                            if(String(this.pay_in_info.txid).indexOf('$search') > -1){
                                return true;
                            }
                        }
                    }
                    if (typeof this.pay_out_info !== 'undefined') {
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

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);
        if(isset($campaign) && $campaign->getCampaignAccount() == $company_id){
            $transactions = $qb
                ->field('status')->notIn(array('deleted'))
                ->field('internal')->equals(true)
                ->sort('updated','desc')
                ->sort('id','desc')
                ->getQuery()
                ->execute();

        }else{
            $transactions = $qb
                ->field('status')->notIn(array('deleted'))
                ->field('internal')->equals(false)
                ->sort('updated','desc')
                ->sort('id','desc')
                ->getQuery()
                ->execute();
        }

        $data = array();
        $dataCustom = array();
        $scales = array();
        $balance = array();
        $volume = array();
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
            $clientsInfo = $em->getRepository('FinancialApiBundle:Client')->findby(array('group' => $company->getId()));
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
        /** @var Transaction $entity */
        foreach ($entities as $entity){
            $entity->setComment($entity->getComment());
            /** @var Group $group */
            $group = $em->getRepository(Group::class)->find($entity->getGroup());
            $group_data = array("type" => $group->getType(), "subtype" => $group->getSubtype());
            $entity->setGroupData($group_data);
            if($entity->getType() === Transaction::$TYPE_OUT){
                $pay_out_info = $entity->getPayOutInfo();
                $receiver = $this->getReceiverFromAddress($em, $pay_out_info['address']);
                if($receiver){
                    $pay_out_info['receiver_type'] = $receiver->getType();
                    $pay_out_info['receiver_subtype'] = $receiver->getSubtype();
                }else{
                    $pay_out_info['receiver_type'] = '-';
                    $pay_out_info['receiver_subtype'] = '-';
                }
                $entity->setPayOutInfo($pay_out_info);
            }else{
                if($entity->getMethod() !== Transaction::$METHOD_LEMONWAY){
                    $pay_in_info = $entity->getPayInInfo();
                    $sender = $em->getRepository(Group::class)->find($pay_in_info['sender_id']);
                    if($sender){
                        $pay_in_info['sender_type'] = $sender->getType();
                        $pay_in_info['sender_subtype'] = $sender->getSubtype();
                    }else{
                        $pay_in_info['sender_type'] = '';
                        $pay_in_info['sender_subtype'] = '';
                    }
                    $entity->setPayInInfo($pay_in_info);
                }
            }

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

    private function getReceiverFromAddress(ObjectManager $em, $address){
        $receiver = $em->getRepository(Group::class)->findOneBy(array(
            'rec_address' => $address
        ));

        if($receiver) return $receiver;

        /** @var PaymentOrder $order */
        $order = $em->getRepository(PaymentOrder::class)->findOneBy(array(
            'payment_address' => $address
        ));

        if($order){
            return $order->getPos()->getAccount();
        }

        return null;

    }
}
