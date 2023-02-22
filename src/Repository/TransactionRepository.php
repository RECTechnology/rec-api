<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 25/8/14
 * Time: 2:22 PM
 */

namespace App\Repository;

use App\Document\Transaction;
use DateInterval;
use DateTime;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Group;

/**
 * Class TransactionRepository
 * @package App\Repository
 */
class TransactionRepository extends DocumentRepository {

    /**
     * @return mixed
     * @throws MongoDBException
     */
    public function count(){
        return $this->createQueryBuilder()
            ->field('internal')->equals(false)
            ->field('deleted')->equals(false)
            ->field('status')->equals('success')
            ->field('currency')->equals('REC')
            ->field('type')->equals('in')
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * @param $start_time
     * @param $end_time
     * @param $interval
     * @return mixed
     */
    public function statistics($start_time, $end_time, $interval){

        $builder = $this->createAggregationBuilder();
        $timeExpr = $builder->expr();
        switch ($interval) {
            case 'day':
                $timeExpr
                    ->field('hour')->hour('$created');
            case 'month':
                $timeExpr
                    ->field('day')->dayOfMonth('$created');
            case 'year':
                $timeExpr
                    ->field('year')->year('$created')
                    ->field('month')->month('$created');
        }
        $builder
            ->match()
                ->field('internal')->equals(false)
                ->field('deleted')->equals(false)
                ->field('status')->equals('success')
                ->field('currency')->equals('REC')
                ->field('type')->equals('in')
                ->field('created')
                    ->gte($start_time)
                    ->lt($end_time)
            ->group()
                ->field('id')
                ->expression($timeExpr)
                ->field('number')
                ->sum(1)
                ->field('volume')
                ->sum('$amount');

        return $builder->execute()->toArray();
    }

    /**
     * @param Group $group
     * @param $start_time
     * @param $finish_time
     * @param $search
     * @param $order
     * @param $dir
     * @return mixed
     * @throws MongoDBException
     */
    public function findTransactions(Group $group, $start_time, $finish_time, $search, $order, $dir){
        return $this->createQueryBuilder('t')
            ->field('group')->equals($group->getId())
            ->field('created')->gte($start_time)
            ->field('created')->lte($finish_time)
            ->where("function() {
            if (typeof this.payInInfo !== 'undefined') {
                if (typeof this.payInInfo.amount !== 'undefined') {
                    if(String(this.payInInfo.amount).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payInInfo.address !== 'undefined') {
                    if(String(this.payInInfo.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payInInfo.status !== 'undefined') {
                    if(String(this.payInInfo.status).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payInInfo.concept !== 'undefined') {
                    if(String(this.payInInfo.concept).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.pay_in_info.reference !== 'undefined') {
                    if(String(this.pay_in_info.reference).indexOf('$search') > -1){
                        return true;
                    }
                }

            }
            if (typeof this.payOutInfo !== 'undefined') {
                if (typeof this.payOutInfo.amount !== 'undefined') {
                    if(String(this.payOutInfo.amount).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.txid !== 'undefined') {
                    if(String(this.payOutInfo.txid).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.address !== 'undefined') {
                    if(String(this.payOutInfo.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.concept !== 'undefined') {
                    if(String(this.payOutInfo.concept).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.email !== 'undefined') {
                    if(String(this.payOutInfo.email).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.find_token !== 'undefined') {
                    if(String(this.payOutInfo.find_token).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.phone !== 'undefined') {
                    if(String(this.payOutInfo.phone).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.pin !== 'undefined') {
                    if(String(this.payOutInfo.pin).indexOf('$search') > -1){
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
                if(String(this._id).indexOf('$search') > -1){ return true;}
                return false;
            }
            return true;
            }")
            ->sort($order,$dir)
            ->getQuery()
            ->execute();
    }

    public function last10Transactions(Group $group){

        return $this->createQueryBuilder('t')
            ->field('group')->equals($group->getId())
            ->field('status')->notEqual('deleted')
            ->limit(10)
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();
    }

    public function getCountryBenefits(Group $group){

        return $this->createQueryBuilder('t')
            ->field('group')->equals($group->getId())
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
    }

    public function getBenefits(Group $group, $start_time, $end_time){

        return $this->createQueryBuilder('t')
            ->field('group')->equals($group->getId())
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
    }

    public function getCompanyTransactions(Request $request, Group $company){

        $qb = $this->createQueryBuilder('t');
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

        return $qb
            ->field('status')->notIn(array('deleted'))
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();
    }

    /**
     * @param Group $group
     * @param $method
     * @param $days
     * @return array[]
     */
    public function sumLastDaysByMethod(Group $group, $method, $days){

        $start_date = new \DateTime();
        $start_date->modify("-".$days." days");

        $builder = $this->createAggregationBuilder();
        $builder
            ->match()
                ->field('group')->equals($group->getId())
                ->field('type')->equals($method->getType())
                ->field('method')->equals($method->getCname())
                ->field('status')->in(['created', 'received', 'success'])
                ->field('created')->gte($start_date)
            ->group()
                ->field('id')
                ->expression('$group')
                ->field('total')
                ->sum('$amount');

        $cursor = $builder->execute();
        $result = $cursor->toArray();

        // this return format is to be backwards compatible with the previous version
        if(!$result) return [['group' => $group->getId(), 'total' => 0]];
        return [['group' => $result[0]['_id'], 'total' => $result[0]['total']]];
    }

    public function sumLastDaysByExchange(Group $group, $to, $days){

        $start_date = new \DateTime();
        $start_date->modify("-".$days." days");

        return $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as last')
            ->field('group')->equals($group->getId())
            ->field('type')->equals('exchange')
            ->field('currency')->equals($to)
            ->field('dataIn.to')->equals($to)
            ->field('total')->gte(0)
            ->field('status')->in(array('created', 'received', 'success'))
            ->field('created')->gte($start_date)
            ->group(
                new \MongoCode('
                    function(trans){
                        return {
                            group : trans.group
                        };
                    }
                '),
                array(
                    'total'=>0
                )
            )
            ->reduce('
                function(trans, result){
                    result.total+=trans.total;
                }
            ')
            ->getQuery()
            ->execute();

    }

    public function getOriginalTxFromTxId($txid, $type){
        $field = 'pay_out_info.txid';
        if($type === Transaction::$TYPE_IN){
            $field = 'pay_in_info.txid';
        }
        return $this->createQueryBuilder('t')
            ->field('service')->in(['rec', 'rosa', 'qbit'])
            ->field($field)->equals($txid)
            ->getQuery()->getSingleResult();
    }

}
