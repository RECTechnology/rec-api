<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class HalcashV2SendService extends BaseService {

    public function getFields(){
        return array(
            'phone_number',
            'phone_prefix',
            'country',
            'amount',
            'reference',
            'pin',
            'transaction_id',
            'alias'
        );
    }

    public function create(Transaction $t){

        $params = $t->getDataIn();

        if($params['country']==='ES'){
            //arreglamos los centimos y el numero de telefono
            //$params[2]=$t->getDataIn()['amount']/100.0;
            //$params[6]=str_replace('+','',$params['phone_number']);

            /*
            $datos=$this->get('halcashsendsp.service')
                ->getHalcashSend($t->getMode())
                ->sendV2($params[0],$params[6],$params[2],$params[3],$params[4],$params[5],$params[7]);
            */
            $halResponse = array(
                'errorcode' => '0',
                'halcashticket' => '1234567890',
            );
            switch($halResponse['errorcode']){
                case 0:
                    $t->setStatus('ISSUED');
                    break;
                case 99:
                    $t->setDataOut($halResponse);
                    throw new HttpException(503, "Service temporally unavailable, maybe deposit account has no funds?");
                default:
                    throw new HttpException(503, "Service Unavailable, please try again later");
            }


        }
        elseif($params['country']==='MX'){
            throw new HttpException(501, "Halcash MX is not available yet");
        }
        else throw new HttpException(400, "Bad country code, allowed ones are MX and ES");

        //Guardamos la respuesta
        $t->setDataOut($halResponse);

        $t->setData(array(
            'pin' => $params['pin'],
            'ticket' => $halResponse['halcashticket']
        ));
        return $t;
    }

    public function update(Transaction $t, $data){

    }

}