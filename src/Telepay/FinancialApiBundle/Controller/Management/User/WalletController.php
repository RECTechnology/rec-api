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
use Telepay\FinancialApiBundle\Entity\UserWallet;


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
        $wallets=$user->getWallets();

        //obtenemos la default currency
        $currency=$user->getDefaultCurrency();

        $filtered=[];
        $available=0;
        $balance=0;

        foreach($wallets as $wallet){
            $filtered[]=$wallet;
            $new_wallet=$this->exchange($wallet,$currency);
            $available=$available+$new_wallet['available'];
            $balance=$balance+$new_wallet['balance'];
        }


        //quitamos el user con to do lo que conlleva detras
        array_map(
            function($elem){
                $elem->setUser(null);
            },
            $filtered
        );

        //montamos el wallet
        $multidivisa=[];
        $multidivisa['id']='multidivisa';
        $multidivisa['currency']=$currency;
        $multidivisa['available']=$available;
        $multidivisa['balance']=$balance;
        $filtered[]=$multidivisa;

        //return $this->rest(201, "Account info got successfully", $filtered);
        return $this->restV2(200, "ok", "Wallet info got successfully", $filtered);

    }

    /**
     * read last ten transactions
     */
    public function last(Request $request){

        $dm = $this->get('doctrine_mongodb')->getManager();

        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $last10Trans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->limit(10)
            ->sort('id','desc')
            ->getQuery()
            ->execute();

        $resArray = [];
        foreach($last10Trans->toArray() as $res){
            $resArray []= $res;

        }

        return $this->rest(
            200, "Last 10 transactions got successfully", $resArray
        );
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

        $transactions = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->sort('id','desc')
            ->getQuery()
            ->execute();


        $resArray = [];
        foreach($transactions->toArray() as $res){
            $resArray []= $res;

        }

        $total = count($resArray);

        $entities = array_slice($resArray, $offset, $limit);


        return $this->rest(
            200,
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

        $day=$this->_getBenefits('day');

        $week=$this->_getBenefits('week');

        $month=$this->_getBenefits('month');


        return $this->rest(
            200,
            "Request successful",
            array(
                'day'       =>  $day,
                'week'      =>  $week,
                'month'     =>  $month,
                'currency'  =>  $default_currency
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

        $day1=date('Y-m-1 00:00:00');

        $monthly=[];

        for($i=0;$i<12;$i++){
            $actual_month=strtotime("-".$i." month",strtotime($day1));
            $next_month=$actual_month+31*24*3600;
            $start_time=strtotime(date('Y-m-d',$actual_month));
            $end_time=strtotime(date('Y-m-d',$next_month));
            $month=$this->_getBenefits('month',$start_time,$end_time);
            $strmonth=getdate($actual_month);
            $monthly[$strmonth['month']]=$month;

        }

        $monthly['currency']=$default_currency;

        return $this->rest(
            200,
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

        $userId=$user->getId();
        $default_currency=$user->getDefaultCurrency();

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

        if(count($result)==0) throw new HttpException(400,'Not transactions found');

        $total=[];

        foreach($result->toArray() as $res){
            $json = file_get_contents('http://www.geoplugin.net/json.gp?ip='.$res['ip']);
            $data = json_decode($json);
            $res['country']=$data->geoplugin_countryName;
            if($res['country']==''){
                $country['name']='not located';
                $country['code']='';
                $country['flag']='';
                $country['value']=$res['total'];
                $total[]=$country;
            }else{
                $country['name']=$data->geoplugin_countryName;
                $country['code']=$data->geoplugin_countryCode;
                $country['flag']=strtolower($data->geoplugin_countryCode);
                $country['value']=$res['total'];
                $total[]=$country;
            }
        }

        //$total['currency']=$default_currency;

        return $this->rest(
            200,
            "Request successful",
            $total
        );
    }

    public function _exchange($amount,$curr_in,$curr_out){

        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found');

        $price=$exchange[0]->getPrice();

        $total=$amount*$price;
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
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
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
    public function exchange(UserWallet $wallet,$currency){

        $currency_actual=$wallet->getCurrency();
        if($currency_actual==$currency){
            $response['available']=$wallet->getAvailable();
            $response['balance']=$wallet->getBalance();
            return $response;
        }
        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findBy(
            array('src'=>$currency_actual,'dst'=>$currency),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found');

        $price=$exchange[0]->getPrice();

        $response['available']=$wallet->getAvailable()*$price;
        $response['balance']=$wallet->getBalance()*$price;
        return $response;

    }

}