<?php

namespace App\Controller\Management\Admin;

use App\Controller\Google2FA;
use App\Controller\Transactions\IncomingController3;
use App\DependencyInjection\Transactions\Core\TransactionUtils;
use App\Document\Transaction;
use App\Entity\Group;
use App\Entity\PaymentOrder;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\SecurityTrait;


/**
 * Class TransactionsController
 * @package App\Controller\Management\Admin
 */
class TransactionsController extends RestApiController {

    use SecurityTrait;

    /**
     * @Rest\View
     */
    public function deleteAction($id){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $dm->remove($trans);
        $dm->flush();

        return $this->rest(204,"ok", "Deleted");
    }

    /**
     * @Rest\View
     */
    public function findAction($id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($trans->getGroup());
        $company = $company->getAdminView();

        $response = array(
            'transaction'   =>  $trans,
            'company'   =>  $company
        );

        return $this->rest(200,"ok", "Transaction found successfully", $this->secureOutput($response));
    }

    /**
     * @Rest\View
     */
    public function listAction(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);
        $search = $request->query->get("search", "");
        $sort = $request->query->get("sort", "updated");
        $order = $request->query->getAlpha("order", "desc");
        $total = 0;

        if($search!=""){
            $qb = array();
            $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($search);
            if($trans){
                $total = 1;
                $payment_info = $trans->getPayInInfo();
                $txid = $payment_info['txid'];
                if($trans->getType()=='in'){
                    $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                        ->field('service')->equals(strtolower($this->getCryptoCurrency()))
                        ->field('pay_out_info.txid')->equals($txid)
                        ->getQuery();
                }
                else{
                    $qb[] = $trans;
                }
            }
        }
        else {
            $start = $request->query->get("start_date", "0");
            if($start!="0"){
                $start_date = new \MongoDate(strtotime($start .' 00:00:00'));
            }
            else{
                $start_date = new \MongoDate(strtotime('-1 month 00:00:00'));
            }

            $finish = $request->query->get("finish_date", "0");
            if($finish!="0"){
                $finish_date = new \MongoDate(strtotime($finish .' 23:59:59'));
            }
            else{
                $finish_date = new \MongoDate(strtotime('now'));
            }

            $total = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals(strtolower($this->getCryptoCurrency()))
                ->field('updated')->gte($start_date)
                ->field('updated')->lte($finish_date)
                ->field('type')->equals('out')
                ->getQuery();

            $total = count($total);

            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals(strtolower($this->getCryptoCurrency()))
                ->field('updated')->gte($start_date)
                ->field('updated')->lte($finish_date)
                ->field('type')->equals('out')
                ->limit($limit)
                ->skip($offset)
                ->getQuery();
        }

        $result = array();
        foreach ($qb as $transaction) {
            $sender = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                'id' => $transaction->getGroup()
            ));

            $payment_info = $transaction->getPayOutInfo();
            $address = $payment_info['address'];

            $receiver = $this->getReceiverFromAddress($em, $address);

            if($receiver){
                $re_id = $receiver->getId();
                $re_type = $receiver->getType();
                $re_subtype = $receiver->getSubtype();
            }
            else{
                $re_id = '-';
                $re_type = '-';
                $re_subtype = '-';

            }

            $result[]=array(
                $transaction->getId(),
                $sender->getId(),
                $sender->getType(),
                $sender->getSubtype(),
                $re_id,
                $re_type,
                $re_subtype,
                $transaction->getMethod(),
                $transaction->getInternal(),
                $transaction->getStatus(),
                $transaction->getAmount(),
                $transaction->getUpdated()
            );
        }

        $sort_cols = [
          'sender_id' => 1,
          'sender_type' => 2,
          'receiver_id' => 4,
          'receiver_type' => 5,
          'method' => 7,
          'amount' => 10,
          'updated' => 11
        ];
        if ($order == "desc"){
            array_multisort(array_column($result, $sort_cols[$sort]), SORT_DESC, $result);
        }else{
            array_multisort(array_column($result, $sort_cols[$sort]), SORT_ASC, $result);
        }

        $data = array(
            'list' => $result,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        );
        return $this->rest(200,"ok", "List transactions generated", $data);
    }

    /**
     * @Rest\View
     */
    public function listActionV3(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);
        $search = $request->query->get("search", "");
        $sort = $request->query->get("sort", "updated");
        $order = $request->query->getAlpha("order", "desc");
        $total = 0;

        if($search!=""){
            $qb = array();
            $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($search);
            if($trans){
                $total = 1;
                $payment_info = $trans->getPayInInfo();
                $txid = $payment_info['txid'];
                if($trans->getType()=='in'){
                    $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                        ->field('service')->equals(strtolower($this->getCryptoCurrency()))
                        ->field('pay_out_info.txid')->equals($txid)
                        ->getQuery();
                }
                else{
                    $qb[] = $trans;
                }
            }
        }
        else {
            $start = $request->query->get("start_date", "0");
            if($start!="0"){
                $start_date = new \MongoDate(strtotime($start .' 00:00:00'));
            }
            else{
                $start_date = new \MongoDate(strtotime('-1 month 00:00:00'));
            }

            $finish = $request->query->get("finish_date", "0");
            if($finish!="0"){
                $finish_date = new \MongoDate(strtotime($finish .' 23:59:59'));
            }
            else{
                $finish_date = new \MongoDate(strtotime('now'));
            }

            $total = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals(strtolower($this->getCryptoCurrency()))
                ->field('updated')->gte($start_date)
                ->field('updated')->lte($finish_date)
                ->field('type')->equals('out')
                ->getQuery();

            $total = count($total);

            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals(strtolower($this->getCryptoCurrency()))
                ->field('updated')->gte($start_date)
                ->field('updated')->lte($finish_date)
                ->field('type')->equals('out')
                ->sort($sort, $order)
                ->limit($limit)
                ->skip($offset)
                ->getQuery();
        }

        $result = array();
        foreach ($qb as $transaction) {
            /** @var Group $sender */
            $sender = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                'id' => $transaction->getGroup()
            ));
            $payment_info = $transaction->getPayOutInfo();
            $address = $payment_info['address'];
            $receiver = $this->getReceiverFromAddress($em, $address);
            if($receiver){
                $re_id = $receiver->getId();
                $re_type = $receiver->getType();
                $re_subtype = $receiver->getSubtype();
            }
            else{
                $re_id = '-';
                $re_type = '-';
                $re_subtype = '-';
            }

            $s_tx = $this->secureOutput($transaction);
            $s_tx['sender_id'] = $sender->getId();
            $s_tx['sender_type'] = $sender->getType();
            $s_tx['sender_subtype'] = $sender->getSubtype();
            $s_tx['receiver_id'] = $re_id;
            $s_tx['receiver_type'] = $re_type;
            $s_tx['receiver_subtype'] = $re_subtype;
            $result[]=$s_tx;
        }

        if ($order == "desc"){
            array_multisort(array_column($result, $sort), SORT_DESC, $result);
        }else{
            array_multisort(array_column($result, $sort), SORT_ASC, $result);
        }

        $data = array(
            'list' => $result,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        );
        return $this->rest(200,"ok", "List transactions generated", $data);
    }

    private function getReceiverFromAddress(ObjectManager $em, $address){
        $receiver = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
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


    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $validParams = array(
            'internal'
        );

        $parameters = $request->request->all();
        $data = array();
        foreach ($parameters as $paramName => $value){
            if(!in_array($paramName, $validParams, false)) throw new HttpException(403, 'Changing '.$paramName.' value');
            $data[$paramName] = $value;
        }

        if(isset($data['internal'])){
            $trans->setInternal($data['internal']);
        }

        $dm->flush();

        return $this->rest(201, 'Transaction updated successfully', '', $trans);
    }

    public function createRefundFromAdmin(Request $request){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        //get data, only txid needed and amount and 2fa
        $validParameters = array(
            'amount',
            'sec_code',
            'txid',
            'concept'
        );
        $requestData = $request->request->all();
        $data = array();
        foreach ($validParameters as $paramName){
            if(!isset($paramName, $requestData[$paramName])){
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
            $data[$paramName] = $request->request->get($paramName);
        }

        //check 2fa
        $adminUser = $this->getUser();
        $Google2FA = new Google2FA();
        $twoFactorCode = $adminUser->getTwoFactorCode();
        if (!$Google2FA::verify_key($twoFactorCode, $data['sec_code'])) {
            throw new HttpException(400,'The security code is incorrect.');
        }

        $dm = $this->getDocumentManager();
        $originalTxIn = $dm->getRepository('FinancialApiBundle:Transaction')->getOriginalTxFromTxId($data['txid'], Transaction::$TYPE_IN);

        if(!$originalTxIn) throw new HttpException(404, 'Transaction not found');
        if($originalTxIn->getType() !== Transaction::$TYPE_IN) throw new HttpException(403, 'Only in transactions can be refund');

        //get refunder account and user
        $em = $this->getEntityManager();
        $group = $em->getRepository(Group::class)->find($originalTxIn->getGroup());

        $version_number = 1;
        $type = 'refund';
        $method_cname = strtolower($this->getCryptoCurrency());
        $user_id = $originalTxIn->getUser();
        $ip = $request->getClientIp();

        $user = $em->getRepository(User::class)->find($user_id);
        $data['pin'] = $user->getPin();
        $response = $this->container
            ->get('app.incoming_controller3')
            ->createTransaction(
                $data, $version_number, $type, $method_cname, $user_id, $group, $ip, $order = null
            );

        $content = json_decode($response->getContent(), true);

        if(isset($content['status']) && $content['status'] === Transaction::$STATUS_SUCCESS){
            $this->container
                ->get('net.app.transactions.core.utils')->makeTransactionsInternal($request, $content);
        }

        return $response;
    }

    /**
     * @return DocumentManager
     */
    private function getDocumentManager(): DocumentManager
    {
        /** @var DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        return $dm;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        return $em;
    }
}
