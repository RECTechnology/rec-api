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