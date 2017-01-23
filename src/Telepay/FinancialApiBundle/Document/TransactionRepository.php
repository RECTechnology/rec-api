<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 25/8/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Telepay\FinancialApiBundle\Entity\Group;

/**
 * Class TransactionRepository
 */
class TransactionRepository extends DocumentRepository {

    public function findTransactions(Group $group, $start_time, $finish_time, $search){
        return $this->createQueryBuilder('t')
            ->field('group')->equals($group->getId())
            //->field('method')->equals($method->getCname())
            //->field('type')->equals($type)
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

}
