<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\UserWallet;


class Test {
    public $name;
    public $address;
    public $address2;
    public $address3;
    public $address4;
    public $address5;

    function __construct($address, $address2, $address3, $address4, $address5, $name)
    {
        $this->address = $address;
        $this->address2 = $address2;
        $this->address3 = $address3;
        $this->address4 = $address4;
        $this->address5 = $address5;
        $this->name = $name;
    }
}

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
        //obtener los wallets
        $wallets = $user->getWallets();

        //obtenemos la default currency
        $currency = $user->getDefaultCurrency();

        $filtered = [];
        $available = 0;
        $balance = 0;
        $scale = 0;

        foreach($wallets as $wallet){
            $filtered[] = $wallet->getWalletView();
            $new_wallet = $this->exchange($wallet, $currency);
            $available = $available + $new_wallet['available'];
            $balance = $balance + $new_wallet['balance'];
            if($new_wallet['scale'] != null) $scale = $new_wallet['scale'];
        }

        //quitamos el user con to do lo que conlleva detras
        /*array_map(
            function($elem){
                $elem->setUser(null);
            },
            $filtered
        );*/

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

        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $last10Trans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
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
     * reads transactions by wallets
     */
    public function walletTransactions(Request $request){

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];

            $transactions = $qb
                ->field('user')->equals($userId)
                ->where("function() {
            if (typeof this.dataIn !== 'undefined') {
                if (typeof this.dataIn.phone !== 'undefined') {
                    if(this.dataIn.phone.indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.address !== 'undefined') {
                    if(this.dataIn.address.indexOf('$search') > -1){
                        return true;
                    }
                }
            }
            return (
            (this.status.indexOf('$search') > -1)
             ||
            (this.service.indexOf('$search') > -1)
             ||
            (String(this._id).indexOf('$search') > -1)
            );}")
                ->sort($order,$dir)
                ->getQuery()
                ->execute();

        }else{
            $order = "id";
            $dir = "desc";

            $transactions = $qb
                ->field('user')->equals($userId)
                ->sort($order,$dir)
                ->getQuery()
                ->execute();
        }

        $resArray = [];
        foreach($transactions->toArray() as $res){
            $resArray []= $res;

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
     * return transaction sum by day. week and month
     */
    public function benefits(Request $request){

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $default_currency=$user->getDefaultCurrency();

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
                'scale'     =>  $this->_getScale($default_currency)
            )
        );
    }

    /**
     * return transaction sum by month (last 12 months)
     */
    public function monthBenefits(Request $request){

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $default_currency=$user->getDefaultCurrency();

        $day1 = date('Y-m-1 00:00:00');

        $monthly = [];

        for($i=0;$i<12;$i++){
            $actual_month = strtotime("-".$i." month",strtotime($day1));
            $next_month = $actual_month+31*24*3600;
            $start_time = strtotime(date('Y-m-d',$actual_month));
            $end_time = strtotime(date('Y-m-d',$next_month));
            $month = $this->_getBenefits('month',$start_time,$end_time);
            $strmonth = date('Y-m',$actual_month);
            $monthly[$strmonth] = $month;

        }

        $monthly['currency'] = $default_currency;

        $monthly['scale'] = $this->_getScale($default_currency);

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

        $userId = $user->getId();
        $default_currency = $user->getDefaultCurrency();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $result = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('status')->equals('success')
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

        $total=[];

        foreach($result->toArray() as $res){
            $json = file_get_contents('http://www.geoplugin.net/json.gp?ip='.$res['ip']);
            $data = json_decode($json);
            $res['country'] = $data->geoplugin_countryName;
            if($res['country'] == ''){
                $country['name'] = 'not located';
                $country['code'] = '';
                $country['flag'] = '';
                $country['value'] = $res['total'];
                $total[] = $country;
            }else{
                $country['name'] = $data->geoplugin_countryName;
                $country['code'] = $data->geoplugin_countryCode;
                $country['flag'] = strtolower($data->geoplugin_countryCode);
                $country['value'] = $res['total'];
                $total[] = $country;
            }
        }

        //$total['currency']=$default_currency;

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

        $parameters = array(
            'username',
            'amount'
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
        $receiver = $em->getRepository('TelepayFinancialApiBundle:User')
            ->findOneBy(array('username'=>$params['username']));

        if (!$receiver) throw new HttpException(404,'Receiver not found');

        //Check founds in sender wallet
        $sender_wallets = $user->getWallets();
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
            'description'   =>  'transfer->'.$currency
        ));
        $sender_transaction->setDataOut(array(
            'sent_to'   =>  $receiver->getUsername(),
            'id_to'     =>  $receiver->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  strtoupper($currency)
        ));
        $sender_transaction->setTotal(-$params['amount']);
        $sender_transaction->setUser($user->getId());


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
            'description'   =>  'transfer->'.$currency
        ));
        $receiver_transaction->setTotal($params['amount']);
        $receiver_transaction->setUser($receiver->getId());

        $dm->persist($receiver_transaction);
        $dm->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($receiver, $params['amount'], $receiver_transaction);

        //todo update wallets
        $sender_wallet->setAvailable($sender_wallet->getAvailable()-$params['amount']);
        $sender_wallet->setBalance($sender_wallet->getBalance()-$params['amount']);

        $receiver_wallet->setAvailable($receiver_wallet->getAvailable()+$params['amount']);
        $receiver_wallet->setBalance($receiver_wallet->getBalance()+$params['amount']);

        $em->persist($sender_wallet);
        $em->persist($receiver_wallet);
        $em->flush();

        return $this->restV2(200, "ok", "Transaction got successfully");


    }

    public function _exchange($amount, $curr_in, $curr_out){

        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found');

        $price = $exchange->getPrice();

        $total = $amount * $price;

        return $total;

    }

    public function _getBenefits($interval,$start = null, $end =null){

        $user = $this->get('security.context')
            ->getToken()->getUser();

        $userId=$user->getId();

        $default_currency=$user->getDefaultCurrency();

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
            ->field('user')->equals($userId)
            ->field('created')->gt($start_time)
            ->field('created')->lt($end_time)
            ->field('status')->equals('success')
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

        $total=0;
        //die(print_r($result,true));
        foreach($result->toArray() as $d){
            if($d['currency']!=''){
                if($default_currency==$d['currency']){
                    $total=$total+$d['total'];
                }else{
                    $change=$this->_exchange($d['total'],$d['currency'],$default_currency);
                    $total=$total+$change;
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

        $response['available'] = $wallet->getAvailable()*$price;
        $response['balance'] = $wallet->getBalance()*$price;
        $response['scale'] = null;

        return $response;

    }

    public  function _getScale($currency){

        $scale=0;
        switch($currency){
            case "EUR":
                $scale=2;
                break;
            case "MXN":
                $scale=2;
                break;
            case "USD":
                $scale=2;
                break;
            case "BTC":
                $scale=8;
                break;
            case "FAC":
                $scale=8;
                break;
            case "PLN":
                $scale=8;
                break;
            case "":
                $scale=0;
                break;
        }

        return $scale;

    }

}