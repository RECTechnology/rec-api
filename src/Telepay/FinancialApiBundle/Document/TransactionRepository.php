<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 25/8/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Document;

use DateInterval;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\Group;

/**
 * Class TransactionRepository
 */
class TransactionRepository extends DocumentRepository {

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
                if (typeof this.payOutInfo.halcashticket !== 'undefined') {
                    if(String(this.payOutInfo.halcashticket).indexOf('$search') > -1){
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
                if(typeof this.methodIn !== 'undefined' && String(this.methodIn).indexOf('$search') > -1){ return true;}
                if(typeof this.methodOut !== 'undefined' && String(this.methodOut).indexOf('$search') > -1){ return true;}
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

        return $qb
            ->field('status')->notIn(array('deleted'))
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();
    }

}
