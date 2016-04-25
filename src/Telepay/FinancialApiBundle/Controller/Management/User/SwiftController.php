<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use FOS\OAuthServerBundle\Model\Client;
use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class SwiftController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function read(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $clients = $user->getClients();
        $em = $this->getDoctrine()->getManager();
        $response = array();

        foreach ($clients as $client){
            $fees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findBy(array(
                'client'    =>  $client->getId()
            ));

            $limits = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findBy(array(
                'client'    =>  $client->getId()
            ));

            $feesCollection = array();
            foreach($fees as $fee){
                $feesCollection[] = array(
                    'id'    =>  $fee->getId(),
                    'cname' =>  $fee->getCname(),
                    'currency'  =>  $fee->getCurrency(),
                    'fixed' =>  $fee->getFixed(),
                    'variable'  =>  $fee->getVariable()
                );
            }

            $limitsCollection = array();
            foreach($limits as $limit){
                $limitsCollection[] = array(
                    'id'    =>  $limit->getId(),
                    'cname' =>  $limit->getCname(),
                    'single' =>  $limit->getSingle(),
                    'day'  =>  $limit->getDay(),
                    'week'  =>  $limit->getWeek(),
                    'month'  =>  $limit->getMonth(),
                    'year'  =>  $limit->getYear(),
                    'total'  =>  $limit->getTotal(),
                );
            }

            $response[] = array(
                'id'    =>  $client->getId(),
                'random_id' =>  $client->getRandomId(),
                'secret'    =>  $client->getSecret(),
                'name'  =>  $client->getName(),
                'fees'  =>  $feesCollection,
                'limits'    =>  $limitsCollection,
                'swift_methods' =>  $client->getSwiftList(),
                'uris'  =>  $client->getRedirectUris()
            );
        }

        return $this->restV2(200, "ok", "Swift info got successfully", $response);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id=null){

        //todo active methods or inactive.
        //get client
        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository('TelepayFinancialApiBundle:Client')->findOneBy(array(
            'id'    =>  $id,
            'user'  =>  $user
        ));

        $swiftMethods = null;
        //To activate swift methods we have to send all the services we want activate
        if($request->request->has('swift_methods')){

            $swiftMethods = $request->get('swift_methods');
            $request->request->remove('swift_methods');
        }

        $response = parent::updateAction($request, $id);

        if($swiftMethods != null){
            if($response->getStatusCode() == 204){
                $client->activeSwiftList($swiftMethods);

                $em->persist($client);
                $em->flush();
            }
        }

        return $response;

    }

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Client";
    }

    function getNewEntity()
    {
        return new Client();
    }

    /**
     * @Rest\View
     */
    public function updateFees(Request $request, $id){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $fee = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->find($id);

        if(!$fee) throw new HttpException(404, 'Fee not found');

        if($user != $fee->getClient()->getUser()) throw new HttpException(403, 'You don\'t have the necessary permissions to change this fee');

        if($request->request->has('fixed')){
            $fee->setFixed($request->request->get('fixed'));
        }

        if($request->request->has('variable')){
            $fee->setVariable($request->request->get('variable'));
         }

        $em->persist($fee);
        $em->flush();

        return $this->restV2(204,"ok", "Updated successfully");


    }

    /**
     * @Rest\View
     */
    public function updateTransaction(Request $request, $id){

        $user = $this->get('security.context')->getToken()->getUser();

        $dm = $this->get('doctrine_mongodb')->getManager();

        if(!$request->request->has('option')) throw new HttpException(404, 'Missing parameter \'option\'');

        $option = $request->request->get('option');

        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'    =>  $id,
            'type'  =>  'swift',
            'user'  =>  $user->getId()
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        $payInInfo = $transaction->getPayInInfo();
        $payOutInfo = $transaction->getPayOutInfo();

        $method_in = $this->get('net.telepay.in.'.$transaction->getMethodIn().'.v1');
        $method_out = $this->get('net.telepay.out.'.$transaction->getMethodOut().'.v1');

        if($option == 'cancel'){
            if($transaction->getStatus() == Transaction::$STATUS_SUCCESS && $payOutInfo['status'] == 'sent'){
                //cancel transaction
                try{
                    $payOutInfo = $method_out->cancel($payOutInfo);
                }catch (HttpException $e){
                    throw new HttpException(400, 'Cancel transaction error');
                }

                $transaction->setPayOutInfo($payOutInfo);
                $transaction->setStatus(Transaction::$STATUS_CANCELLED);
                $transaction->setUpdated(new \DateTime());
                $message = 'Transaction cancelled successfully';

            }else{
                throw new HttpException(403, 'Transaction can\'t be cancelled');
            }
        }elseif($option == 'resend'){
            if($transaction->getStatus() == Transaction::$STATUS_FAILED || $transaction->getStatus() == Transaction::$STATUS_CANCELLED){
                //resend out method
                try{
                    $payOutInfo = $method_out->send($payOutInfo);
                }catch (HttpException $e){
                    throw new HttpException(400, 'Resend transaction error');
                }

                //TODO if previous status = failed generate fees transactions

                $transaction->setPayOutInfo($payOutInfo);
                $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                $transaction->setUpdated(new \DateTime());
                $message = 'Transaction resend successfully';

            }
        }else{
            throw new HttpException(400, 'Bad parameter \'option\'');
        }

        $dm->persist($transaction);
        $dm->flush();

        return $this->restV2(204,"ok", $message);

    }

}