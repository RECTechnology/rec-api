<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 6/24/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class POSIncomingController extends RestApiController{

    /**
     * @Rest\View
     */
    public function createTransaction(Request $request, $version_number,  $id){
        $em = $this->getDoctrine()->getManager();
        $tpvRepo = $em->getRepository('TelepayFinancialApiBundle:POS')->findOneBy(array(
            'pos_id'    =>  $id
        ));

        $service_cname = $tpvRepo->getCname();
        $group = $tpvRepo->getGroup();
        if($tpvRepo->getActive() == 0) throw new HttpException(400, 'Service Temporally unavailable');

        $service_currency = strtoupper($tpvRepo->getCurrency());
        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);
        $dataIn = array();
        foreach($service->getFields() as $field){
            if(!$request->request->has($field))
                throw new HttpException(400, "Parameter '".$field."' not found");
            else $dataIn[$field] = $request->get($field);
        }
        if($dataIn['currency'] != $service_currency) throw new HttpException(403, 'Currency not allowed');
        $dm = $this->get('doctrine_mongodb')->getManager();

        //Check unique order_id by group and tpv
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('posId')->equals($id)
            ->field('group')->equals($group->getId())
            ->field('dataIn.order_id')->equals($dataIn['order_id'])
            ->getQuery();

        if( count($qb) > 0 ) throw new HttpException(409,'Duplicated resource');

        //create transaction
        $transaction = Transaction::createFromRequest($request);
        $transaction->setService($service_cname);
        $transaction->setGroup($group->getId());
        $transaction->setVersion($version_number);
        $transaction->setDataIn($dataIn);
        $transaction->setPayInInfo($dataIn);
        $transaction->setPosId($id);
        $dm->persist($transaction);
        $amount = $dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        $fixed_fee = 0;
        $variable_fee = 0;
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $dm->persist($transaction);

        $total = $amount - $variable_fee - $fixed_fee;
        $transaction->setTotal($amount);

        //TODO obtain wallet and check founds for cash_out services for this group
        $wallets = $group->getWallets();

        $current_wallet = null;

        $transaction->setCurrency($service_currency);

        //CASH - IN

        try {
            $transaction = $service->create($transaction);
        }catch (HttpException $e){
            if($transaction->getStatus() === Transaction::$STATUS_CREATED)
                $transaction->setStatus(Transaction::$STATUS_FAILED);
            $this->container->get('notificator')->notificate($transaction);
            $dm->persist($transaction);
            $dm->flush();
            throw $e;
        }

        $transaction = $this->get('notificator')->notificate($transaction);
        $em->flush();

        foreach ( $wallets as $wallet){
            if ($wallet->getCurrency() === $transaction->getCurrency()){
                $current_wallet = $wallet;
            }
        }

        //TODO update wallet amount (only balance not the available amount)

        $scale = $current_wallet->getScale();
        $transaction->setScale($scale);

        $transaction->setUpdated(new \DateTime());

        $dm->persist($transaction);
        $dm->flush();

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        return $this->restTransaction($transaction, "Done");
    }

    /**
     * @Rest\View
     */
    public function createTransactionV2(Request $request,  $id){

        $em = $this->getDoctrine()->getManager();
        $tpvRepo = $em->getRepository('TelepayFinancialApiBundle:POS')->findOneBy(array(
            'pos_id'    =>  $id
        ));

        $posType = $tpvRepo->getType();

        $group = $tpvRepo->getGroup();

        if($tpvRepo->getActive() == 0) throw new HttpException(400, 'Service Temporally unavailable');

        $pos_config = $this->container->get('net.telepay.config.pos_'.strtolower($posType))->getInfo();

        $paramNames = array(
            'amount',
            'concept',
            'currency_in',
            'url_notification',
            'url_ok',
            'url_ko',
            'order_id',
            'signature'
        );

        $dataIn = array();
        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName))
                throw new HttpException(400, "Parameter '".$paramName."' not found");
            else $dataIn[$paramName] = $request->get($paramName);
        }

        $data_to_sign = $dataIn['order_id'] . $id . $dataIn['amount'];
        $signature_test = hash_hmac('sha256', $data_to_sign, $group->getAccessSecret());

        if($dataIn['signature'] != $signature_test) {
            throw new HttpException(404, 'Bad signature');
        }

        if($request->request->has('currency_out')){
            $dataIn['currency_out'] = $request->request->get('currency_out');
        }else{
            $dataIn['currency_out'] = $pos_config['default_currency'];
        }

        $dm = $this->get('doctrine_mongodb')->getManager();

        //Check unique order_id by group and tpv
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('posId')->equals($id)
            ->field('group')->equals($group->getId())
            ->field('dataIn.order_id')->equals($dataIn['order_id'])
            ->getQuery();

        if( count($qb) > 0 ) throw new HttpException(409,'Duplicated resource');

        if(!in_array(strtoupper($dataIn['currency_in']), $pos_config['allowed_currencies_in'])){
            throw new HttpException(404, 'Currency_in not allowed');
        }
        if(!in_array(strtoupper($dataIn['currency_out']), $pos_config['allowed_currencies_out'])){
            throw new HttpException(404, 'Currency_out not allowed');
        }

        if(strtoupper($dataIn['currency_in']) != $pos_config['currency']){
            $exchange = $em->getRepository('TelepayFinancialApiBundle:Exchange')->findOneBy(
                array(
                    'dst'   =>  $dataIn['currency_in'],
                    'src'   =>  $pos_config['currency']
                ),
                array('id'  =>  'DESC')
            );
            $pos_amount = round($dataIn['amount']*$exchange->getPrice(),0);
        }else{
            $pos_amount = $dataIn['amount'];
        }

        if(strtoupper($dataIn['currency_out']) != $pos_config['currency']){
            $exchange = $em->getRepository('TelepayFinancialApiBundle:Exchange')->findOneBy(
                array(
                    'src'   =>  $pos_config['currency'],
                    'dst'   =>  $dataIn['currency_out']
                ),
                array('id'  =>  'DESC')
            );
            $amount = round($pos_amount * $exchange->getPrice(),0);
        }else{
            $amount = $pos_amount;
        }

        //create transaction
        $transaction = Transaction::createFromRequest($request);
        $transaction->setService('POS-'.$posType);
        $transaction->setMethod('POS-'.$posType);
        $transaction->setGroup($group->getId());
        $transaction->setVersion(1);
        $transaction->setDataIn($dataIn);
        $transaction->setPayInInfo($dataIn);
        $transaction->setPosId($id);
        $dm->persist($transaction);
        $transaction->setAmount($amount);
        $transaction->setType('POS-'.$posType);

        $transaction->setLastPriceAt(new \DateTime());
        $transaction->setLastCheck(new \DateTime());
        $transaction->setPosName($tpvRepo->getName());

        //get fees from group
        $group_commission = $this->_getFees($group, 'POS-'.$posType, strtoupper($dataIn['currency_out']));

        //add commissions to check
        $fixed_fee = $group_commission->getFixed();
        $variable_fee = round(($group_commission->getVariable()/100) * $amount, 0);

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $total = $amount + $variable_fee + $fixed_fee;
        $transaction->setTotal($total);
        $dm->persist($transaction);

        $current_wallet = null;

        $transaction->setCurrency(strtoupper($dataIn['currency_out']));
        $transaction->setScale(Currency::$SCALE[strtoupper($dataIn['currency_out'])]);

        //CASH - IN
        //distinguirn entre los distintos tipos de tpv
        if($posType == 'PNP'){
            $trans_pos_id = rand();
            $paymentInfo = array(
                'amount'    =>  $amount,
                'currency'  =>  'EUR',
                'scale'     =>  Currency::$SCALE['EUR'],
                'transaction_pos_id'    =>  $trans_pos_id,
                'url_ok'    =>  $dataIn['url_ok'],
                'url_ko'    =>  $dataIn['url_ko']
            );
        }elseif($posType == 'BTC'){
            $address = $this->generateAddress($posType);
            if(!$address) throw new HttpException(403, 'Service temporally unavailable');
            $paymentInfo = array(
                'amount'    =>  $pos_amount,
                'previous_amount'    =>  $pos_amount,
                'received_amount'   =>  $dataIn['amount'],
                'currency_in'   =>  strtoupper($dataIn['currency_in']),
                'currency'  =>  'BTC',
                'scale'     =>  Currency::$SCALE['BTC'],
                'scale_in'     =>  Currency::$SCALE[strtoupper($dataIn['currency_in'])],
                'address'   =>  $address,
                'expires_in'=>  $tpvRepo->getExpiresIn(),
                'received'  =>  0,
                'min_confirmations' =>  0,
                'confirmations' =>  1,
                'url_ok'    =>  $dataIn['url_ok'],
                'url_ko'    =>  $dataIn['url_ko']
            );
        }elseif($posType == 'FAC'){
            $address = $this->generateAddress($posType);
            if(!$address) throw new HttpException(403, 'Service temporally unavailable');
            $paymentInfo = array(
                'amount'    =>  $pos_amount,
                'previous_amount'    =>  $pos_amount,
                'received_amount'   =>  $dataIn['amount'],
                'currency_in'   =>  strtoupper($dataIn['currency_in']),
                'currency'  =>  'FAC',
                'scale'     =>  Currency::$SCALE['FAC'],
                'scale_in'     =>  Currency::$SCALE[strtoupper($dataIn['currency_in'])],
                'address'   =>  $address,
                'expires_in'=>  $tpvRepo->getExpiresIn(),
                'received'  =>  0,
                'min_confirmations' =>  0,
                'confirmations' =>  1,
                'url_ok'    =>  $dataIn['url_ok'],
                'url_ko'    =>  $dataIn['url_ko']
            );
        }elseif($posType == 'SAFETYPAY'){
            $paymentInfo = array(
                'amount'        =>  $dataIn['amount'],
                'scale'         =>  Currency::$SCALE[$dataIn['currency_in']],
                'currency'      =>  $dataIn['currency_in'],
                'expires_in'    =>  $tpvRepo->getExpiresIn(),
                'url_ok'        =>  $dataIn['url_ok'],
                'url_ko'        =>  $dataIn['url_ko']
            );
        }elseif($posType == 'SABADELL') {
            $trans_pos_id = rand();
            $parameters = "";
            $signature = "";
            $paymentInfo = array(
                'amount' => $amount,
                'currency' => 'EUR',
                'scale' => Currency::$SCALE['EUR'],
                'transaction_pos_id' => $trans_pos_id,
                'parameters' => $parameters,
                'signature' => $signature,
                'url_ok' => $dataIn['url_ok'],
                'url_ko' => $dataIn['url_ko']
            );
        }
        $transaction->setPayInInfo($paymentInfo);
        $em->flush();
        $transaction->setUpdated(new \DateTime());
        $transaction = $this->get('notificator')->notificate($transaction);
        $dm->persist($transaction);
        $dm->flush();
        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");
        return $this->posTransaction(201, $transaction, "Done");
    }

    /**
     * @Rest\View
     */
    public function generateAddress($type){
        if($type == 'BTC'){
            return $this->container->get('net.telepay.provider.btc')->getnewaddress();
        }
        elseif ($type == 'FAC'){
            return $this->container->get('net.telepay.provider.fac')->getnewaddress();
        }
    }

    /**
     * @Rest\View
     */
    public function checkTransaction(Request $request, $id){
        $em = $this->get('doctrine_mongodb')->getManager();
        $transaction = $em->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);
        return $this->posTransaction(200,$transaction, "Got ok");
    }

    /**
     * @Rest\View
     */
    public function checkTransaction2(Request $request, $id){

        $command = $this->container->get('command.check.posV2');
        $input = new ArgvInput(
            array(
                '--env=' . $this->container->getParameter('kernel.environment'),
                '--transaction-id=' . $id
            )
        );
        $output = new BufferedOutput();
        $command->run($input, $output);

        $em = $this->get('doctrine_mongodb')->getManager();
        $transaction = $em->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);
        if(!$transaction) throw new HttpException(400,'Transaction not found');
        if($id==$output) {
            return $this->posTransaction(201, $transaction, "Checked ok");
        }
        return $this->posTransaction(200, $transaction, "Got ok");
    }

    /**
     * @Rest\View
     */
    public function find(Request $request, $version_number, $pos_id){
        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $pos = $em->getRepository('TelepayFinancialApiBundle:POS')->findOneBy(array(
            'pos_id'  =>  $pos_id,
            'group'  =>  $userGroup
        ));
        if(empty($pos)) throw new HttpException(404, "Not found");

        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];
            $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));//date('Y-m-d 00:00:00')
            $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));

            $transactions = $qb
                ->field('posId')->equals($pos_id)
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->where("function() {
            if (typeof this.dataIn !== 'undefined') {
                if (typeof this.dataIn.order_id !== 'undefined') {
                    if(String(this.dataIn.order_id).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.description !== 'undefined') {
                    if(String(this.dataIn.description).indexOf('$search') > -1){
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

            }
            if(typeof this.status !== 'undefined' && String(this.status).indexOf('$search') > -1){ return true;}
            if(typeof this.amount !== 'undefined' && String(this.amount).indexOf('$search') > -1){ return true;}
            if(String(this._id).indexOf('$search') > -1){ return true;}

            return false;
            }")
                ->sort($order,$dir)
                ->getQuery()
                ->execute();

        }else{
            $order = "id";
            $dir = "desc";

            $transactions = $qb
                ->field('posId')->equals($pos_id)
                ->sort($order,$dir)
                ->getQuery()
                ->execute();
        }
        $resArray = [];
        foreach($transactions->toArray() as $res){
            $resArray []= $res;

        }

        $total = count($resArray);

        $page_amount = 0;
        $total_amount = 0;

        foreach ($resArray as $array){
            if($array->getStatus() == 'success'){
                $total_amount = $total_amount + $array->getAmount();
            }
        }

        $entities = array_slice($resArray, $offset, $limit);

        foreach ($entities as $ent){
            if($ent->getStatus() == 'success'){
                $page_amount = $page_amount + $ent->getAmount();
            }
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities,
                'page_amount' => $page_amount,
                'total_amount' => $total_amount
            )
        );
    }

    private function _getFees(Group $group, $method, $currency){

        $em = $this->getDoctrine()->getManager();

        $group_commissions = $group->getCommissions();
        $group_commission = false;

        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $method && $commission->getCurrency() == $currency ){
                $group_commission = $commission;
            }
        }

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($method, $group);
            $group_commission->setCurrency($currency);
            $em->persist($group_commission);
            $em->flush();
        }

        return $group_commission;
    }

    /**
     * @Rest\View
     */
    public function cancelTransaction2(Request $request, $id){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getStatus() == Transaction::$STATUS_CREATED) {
            $transaction->setStatus(Transaction::$STATUS_CANCELLED);
            $transaction = $this->get('notificator')->notificate($transaction);
            $dm->persist($transaction);
            $dm->flush();
        }
        return $this->restV2(204, "ok", "Update successfully");

    }

}


