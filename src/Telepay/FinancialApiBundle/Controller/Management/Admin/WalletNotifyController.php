<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\Client;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class WalletNotifyController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class WalletNotifyController extends RestApiController {



    /**
     * @Rest\View
     */
    public function notifyAction(Request $request){

        $txid = $request->request->get('txid');
        exec('curl -X POST -d "chat_id=-217902377&text=#wallet_notification '.$txid.'" "https://api.telegram.org/bot337240065:AAGeA0vqJ06ZaIkkNyNQWunXjo_1OX5_z1E/sendMessage"');
//        exec('curl -X POST -d "txid=blablabla" "http://127.0.0.1:8000/admin/v1/wallet/notify"');

        return $this->restV2(200, 'success', "Notified successfully");
    }



}
