<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\Scheduled;

class ScheduledController extends BaseApiController{

    function getRepositoryName()
    {
        return "FinancialApiBundle:Scheduled";
    }

    function getNewEntity()
    {
        return new Scheduled();
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userGroup = $user->getActiveGroup();

        if(!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) throw new HttpException(403, 'You don\' have the necessary permissions');

        $request->request->add(array(
            'group'   =>  $userGroup
        ));

        if(!$request->request->has('wallet')) throw new HttpException(400,'Missing parameter wallet');
        $wallet = $request->request->get('wallet');
        if($wallet != "EUR"){
            throw new HttpException(400,'Unavailable value for parameter wallet');
        }

        if(!$request->request->has('method')) throw new HttpException(400,'Missing parameter method');
        $method = $request->request->get('method');
        if($method != "sepa"){
            throw new HttpException(400,'Unavailable value for parameter method');
        }

        if(!$request->request->has('period')) throw new HttpException(400,'Missing parameter period');
        $period = $request->request->get('period');
        // 0 => monthly
        // 1 => daily
        $periods_availables = array("0", "1");
        if(!in_array($period, $periods_availables)){
            throw new HttpException(400,'Unavailable value for parameter period');
        }

        if(!$request->request->has('minimum')) throw new HttpException(400,'Missing parameter minimum');
        if(!$request->request->has('threshold')) throw new HttpException(400,'Missing parameter threshold');

        if($method == "sepa"){
            if(!$request->request->has('iban')) throw new HttpException(400,'Missing parameter iban');
            $iban = $request->request->get('iban');
            if(!$this->checkIBAN($iban)){
                throw new HttpException(400,'Iban incorrect');
            }
            $request->request->remove('iban');

            if(!$request->request->has('swift')) throw new HttpException(400,'Missing parameter swift');
            $swift = $request->request->get('swift');
            if(strlen($swift)<8 && strlen($swift)>11){
                throw new HttpException(400,'Swift length incorrect');
            }
            $request->request->remove('swift');

            if(!$request->request->has('concept')) throw new HttpException(400,'Missing parameter concept');
            $concept = $request->request->get('concept');
            if(strlen($concept)<1){
                throw new HttpException(400,'Concept must be defined');
            }
            $request->request->remove('concept');

            if(!$request->request->has('beneficiary')) throw new HttpException(400,'Missing parameter beneficiary');
            $beneficiary = $request->request->get('beneficiary');
            if(strlen($beneficiary)<1){
                throw new HttpException(400,'Beneficiary must be defined');
            }
            $request->request->remove('beneficiary');

            $request->request->add(array(
                'info'    =>  json_encode(array(
                        "iban" => $iban,
                        "swift" => $swift,
                        "concept" => $concept,
                        "beneficiary" => $beneficiary
                    )
                ))
            );
        }
        return parent::createAction($request);
    }

    /**
     * @Rest\View
     */
    public function indexAction(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $all = $this->getRepository()->findBy(array(
            'group'  =>  $user->getActiveGroup()
        ));

        $total = count($all);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all
            )
        );
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $scheduled = $this->getRepository()->findOneBy(array(
            'group'  =>  $user->getActiveGroup(),
            'id'    =>  $id
        ));

        if(!$scheduled) throw new HttpException(404, 'Scheduled not found');

        if($request->request->has('method')) throw new HttpException(403, 'Method field can\'t be changed');
        if($request->request->has('wallet')) throw new HttpException(403, 'Wallet field can\'t be changed');

        if($request->request->has('period')) {
            $period = $request->request->get('period');
            // 2 => monthly
            // 1 => weekly
            // 0 => daily
            $periods_availables = array("0", "1", "2");
            if(!in_array($period, $periods_availables)){
                throw new HttpException(400,'Unavailable value for parameter period');
            }
        }

        if($request->request->has('iban')
            || $request->request->has('swift')
            || $request->request->has('concept')
            || $request->request->has('beneficiary')
        ){

            $currentInfo = json_decode($scheduled->getInfo());

            if($request->request->has('iban')){
                $iban = $request->request->get('iban');
                if(!$this->checkIBAN($iban)){
                    throw new HttpException(400,'Iban incorrect');
                }
                $request->request->remove('iban');
            }else{
                $iban = $currentInfo['iban'];
            }

            if($request->request->has('swift')){
                $swift = $request->request->get('swift');
                if(strlen($swift)<8 && strlen($swift)>11){
                    throw new HttpException(400,'Swift length incorrect');
                }
                $request->request->remove('swift');
            }else{
                $swift = $currentInfo['swift'];
            }

            if($request->request->has('concept')){
                $concept = $request->request->get('concept');
                $request->request->remove('concept');
            }else{
                $concept = $currentInfo['concept'];
            }

            if($request->request->has('beneficiary')){
                $beneficiary = $request->request->get('beneficiary');
                $request->request->remove('beneficiary');
            }else{
                $beneficiary = $currentInfo['beneficiary'];
            }

            $request->request->add(array(
                'info'    =>  json_encode(array(
                    "iban" => $iban,
                    "swift" => $swift,
                    "concept" => $concept,
                    "beneficiary" => $beneficiary
                    )
                ))
            );
        }

        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $scheduled = $this->getRepository()->findOneBy(array(
            'id'    =>  $id,
            'group' =>  $user->getActiveGroup()
        ));

        if(!$scheduled) throw new HttpException(404, 'Scheduled not found');

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

            if(bcmod($NewString, '97') == 1)
            {
                return TRUE;
            }
            else{
                return FALSE;
            }
        }
        else{
            return FALSE;
        }
    }
}