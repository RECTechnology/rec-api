<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class SettingsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Company
 */
class SettingsController extends RestApiController {

    /**
     * reads information about all wallets for a company provided
     * permissions: all in this company allowed
     */
    public function readSettings($company_id){

        $em = $this->getDoctrine()->getManager();

        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);

        if(!$company) throw new HttpException(404, 'Company not found');

        $user = $this->getUser();

        $this->_checkPermissions($user, $company);
        //SETTINGS
        // currencies / notifications /

        //settings from currencies are in wallets
        $wallets = $em->getRepository('TelepayFinancialApiBundle:UserWallet')->findBy(array(
            'group' =>  $company
        ));

        $settings = array();
        $currencies = array();

        foreach ($wallets as $wallet){
            $currencies[$wallet->getCurrency()] = $wallet->getStatus();
        }
        $settings['currencies'] = $currencies;

        return $this->restV2(200, "ok", "Settings got successfully", $settings);
    }

   /**
    * @Rest\View
    */
   public function updateSettings(Request $request, $company_id){
       $em = $this->getDoctrine()->getManager();

       $user = $this->getUser();
       $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);

       if(!$company) throw new HttpException(404, 'Company not found');
       //check if this user is on this company and is role worker at less

       $this->_checkPermissions($user, $company);

       if($request->request->has('currencies')){

           $currencies = $request->request->get('currencies');
           foreach ($currencies as $currency=>$value){
               //get compnay wallet by currency
               $wallet = $em->getRepository('TelepayFinancialApiBundle:UserWallet')->findOneBy(array(
                   'group'  =>  $company,
                   'currency'   =>  strtoupper($currency)
               ));

               if(!$wallet) throw new HttpException(404, 'Wallet not found');
               //value only can be enabled or disabled
               if($value != 'enabled' && $value != 'disabled') throw new HttpException(403, 'Value must be enabled or disabled');

               $wallet->setStatus($value);

               $em->flush();
           }
       }else{
           throw new HttpException(404, 'Params not found');
       }

       return $this->restV2(204, 'success', 'updated successfully');



   }

    private function _checkPermissions(User $user, Group $group){

        if(!$user->hasGroup($group->getName())) throw new HttpException(403, 'You(' . $user->getId() . ') do not have the necessary permissions in this company(' . $group->getId() . ')');

        //Check permissions for this user in this company
        $userRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $group->getId()
        ));

        if(!$userRoles->hasRole('ROLE_WORKER') && !$userRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions in this company. Only ROLE_WORKER allowed');


    }
}
