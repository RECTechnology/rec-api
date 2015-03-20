<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;
use Telepay\FinancialApiBundle\Controller\RestApiController;


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

        $filtered=[];

        foreach($wallets as $wallet){
            $filtered[]=$wallet;
        }

        //quitamos el user con to do lo que conlleva detras
        array_map(function($elem){
            $elem->setUser(null);
        }, $filtered);

        return $this->rest(200, "Account info got successfully", $filtered);

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
    public function exchange(){

    }

}