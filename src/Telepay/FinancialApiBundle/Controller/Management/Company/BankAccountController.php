<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\BankAccount;
use Telepay\FinancialApiBundle\Controller\BaseApiController;

class BankAccountController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:BankAccount";
    }

    function getNewEntity()
    {
        return new BankAccount();
    }

    /**
     * @Rest\View
     */
    public function registerAccount(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $group = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();

        $paramNames = array(
            'owner',
            'iban'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        if(!$this->checkIBAN($params['iban'])){
            throw new HttpException(404, 'Incorrect IBAN');
        }

        $em = $this->getDoctrine()->getManager();
        $bank = new BankAccount();
        $bank->setCompany($group);
        $bank->setUser($user);
        $bank->setOwner($params['owner']);
        $bank->setIban($params['iban']);
        $em->persist($bank);
        $em->flush();
        return $this->restV2(201,"ok", "Bank account registered successfully", $bank);
    }

    /**
     * @Rest\View
     */
    public function indexAccounts(Request $request){
        $user = $this->getUser();
        $company = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $accounts = $em->getRepository('TelepayFinancialApiBundle:BankAccount')->findBy(array(
            'company'   =>  $company,
            'user'   =>  $user
        ));
        return $this->restV2(200, 'ok', 'Request successfull', $accounts);
    }

    /**
     * @Rest\View
     */
    public function updateAccountFromCompany(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $account = $em->getRepository('TelepayFinancialApiBundle:BankAccount')->find($id);

        if($account->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$account) throw new HttpException(404, 'Bank account not found');

        if($request->request->has('iban')){
            if(!$this->checkIBAN($request->request->get('iban'))){
                throw new HttpException(404, 'Incorrect IBAN');
            }
            $account->setIban($request->request->get('iban'));
        }

        if($request->request->has('owner')){
            $account->setOwner($request->request->get('owner'));
        }

        $em->persist($account);
        $em->flush();
        return $this->restV2(204, 'ok', 'Bank account updated successfully');
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $account = $em->getRepository('TelepayFinancialApiBundle:BankAccount')->findOneBy(array(
            'id'    =>  $id,
            'company' =>  $user->getActiveGroup()
        ));

        if(!$account) throw new HttpException(404, 'Bank Account not found');

        return parent::deleteAction($id);
    }

    private function checkIBAN($iban){
        $iban = strtolower(str_replace(' ','',$iban));
        $Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
        $Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

        if(strlen($iban) == $Countries[substr($iban,0,2)]){

            $MovedChar = substr($iban, 4).substr($iban,0,4);
            $MovedCharArray = str_split($MovedChar);
            $NewString = "";

            foreach($MovedCharArray AS $key => $value){
                if(!is_numeric($MovedCharArray[$key])){
                    $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
                }
                $NewString .= $MovedCharArray[$key];
            }

            if($this->my_bcmod($NewString, '97') == 1)
            {
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    private function my_bcmod( $x, $y ){
        // how many numbers to take at once? carefull not to exceed (int)
        $take = 5;
        $mod = '';

        do{
            $a = (int)$mod.substr( $x, 0, $take );
            $x = substr( $x, $take );
            $mod = $a % $y;
        }
        while ( strlen($x) );

        return (int)$mod;
    }

}