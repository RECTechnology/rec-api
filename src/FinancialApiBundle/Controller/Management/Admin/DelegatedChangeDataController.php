<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Admin;

use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\Tier;
use AssertionError;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use Documents\Account;
use FOS\RestBundle\Controller\Annotations as Rest;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\Group;

/**
 * Class DelegatedChangeDataController
 * @package App\FinancialApiBundle\Controller\Management\Admin
 */
class DelegatedChangeDataController extends BaseApiController{

    const DELEGATED_CHANGE_CSV_HEADERS = [
        "account",
        "exchanger",
        "amount",
        "creditcard"
    ];
    const  MASSIVE_TRANSACTIONS_CSV_HEADERS = [
        "account",
        "amount",
        "sender",
        "exchanger"
    ];

    /**
     * @param Request $request
     * @return Response
     * @Rest\View
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     * @Rest\View
     */
    public function createAction(Request $request)
    {
        return parent::createAction($request);
    }

    /**
     * @param $id
     * @return Response
     * @Rest\View
     */
    public function showAction($id)
    {
        return parent::showAction($id);
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     * @Rest\View
     */
    public function updateAction(Request $request, $id) {
        return parent::updateAction($request, $id);
    }


    /**
     * @param $id
     * @return Response
     * @Rest\View
     */
    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }


    /**
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws ReflectionException
     * @Rest\View
     */
    public function importAction(Request $request){

        if (!$request->request->has('path'))
            throw new HttpException(400, "Path is required");

        if (!$request->request->has('delegated_change_id'))
            throw new HttpException(400, "Delegated_change_id is required");

        $dc_id = $request->request->get('delegated_change_id');

        $em = $this->getDoctrine()->getManager();
        $dc = $em->getRepository(DelegatedChange::class)->find($dc_id);

        if (!isset($dc)) throw new HttpException(400, "No delegated_change found");

        if (!in_array($dc->getStatus(), [DelegatedChange::STATUS_CREATED, DelegatedChange::STATUS_INVALID]))
            throw new HttpException(400, "This transaction block already has csv");

        if (count($dc->getData()) > 0)
            throw new HttpException(400, "This transaction block already has transaction block data");

        $fileSrc = $request->request->get('path');

        $fileHandler = fopen($fileSrc, "r");
        if(mime_content_type($fileHandler) !== "text/plain") throw new HttpException(400,"The file is not a CSV");
        if(filesize($fileSrc) == 0) throw new HttpException(400,"The file not contain any data");

        $fileHeaders = fgetcsv($fileHandler, 1000);
        $requiredHeaders = ["sender", "exchanger", "account", "amount"];
        if($requiredHeaders !== $fileHeaders) throw new HttpException(400,"Missing required headers");

        $firstRow = fgetcsv($fileHandler, 1000);
        if(count($firstRow) == 0) throw new HttpException(400,"No data found");
        if(count($firstRow) != 4) throw new HttpException(400,"No valid data found");


        $dc->setUrlCsv($request->request->get('path'));
        $dc->setStatus(DelegatedChange::STATUS_PENDING_VALIDATION);
        $em->flush();

        return $this->restV2(201, "success", "CSV added successfully");

    }




    /**
     * @param $csvContents
     * @return array
     */
    private function csvToArray($csvContents){
        $tmpLocation = '/tmp/' . uniqid("upload_") . ".tmp.csv";
        file_put_contents($tmpLocation, $csvContents);

        $contents = [];
        $delimiter = ';';
        if (($handle = fopen($tmpLocation, "r")) !== false) {
            if(($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if(count($row) == 1){
                    fseek($handle, 0);
                    $delimiter = ',';
                    $row = fgetcsv($handle, 0, $delimiter);
                }
                $headers = [];
                foreach ($row as $hdr) {
                    $headers []= trim($hdr);
                }
            }
            else throw new HttpException(400, "Invalid CSV: csv file must contain at least the headers row");

            while(($row = fgetcsv($handle, 0 ,$delimiter)) !== false) {
                $rowArr = [];
                $rowLen = count($row);
                for($i=0; $i<$rowLen; $i++){
                    $rowArr[$headers[$i]] = $row[$i];
                }

                $contents []= $rowArr;
            }
        }
        fclose($handle);
        unlink($tmpLocation);
        return $contents;
    }


    public function getRepositoryName() {
        return "FinancialApiBundle:DelegatedChangeData";
    }

    function getNewEntity() {
        return new DelegatedChangeData();
    }

    private function checkExchangerConstraints(Tier $kyc, Group $exchanger){
        if ($kyc->getCode() != "KYC2") {
            throw new HttpException(
                400,
                sprintf("Exchanger (%s) KYC lower than KYC2.", $exchanger->getId()));
        }
        if (!$exchanger->getActive()) {
            throw new HttpException(
                400,
                printf("Exchanger (%s) is not active.", $exchanger->getId()));
        }

        if (!$exchanger->getKycManager()->isEnabled()) {
            throw new HttpException(
                400,
                printf("Exchanger (%s) user is not enabled.", $exchanger->getId()));
        }
        if (!$exchanger->getKycManager()->isAccountNonLocked()) {
            throw new HttpException(
                400,
                sprintf("Exchanger (%s) user is locked.", $exchanger->getId()));
        }
    }

    private function checkAccountConstraints(Group $account){
        if (!$account->getActive()) {
            throw new HttpException(
                400,
                printf("Account (%s) is not active.", $account->getId()));
        }

        if (!$account->getKycManager()->isEnabled()) {
            throw new HttpException(
                400,
                sprintf("Account (%s) user is not enabled.", $account->getId()));
        }
        if (!$account->getKycManager()->isAccountNonLocked()) {
            throw new HttpException(
                400,
                sprintf("Account (%s) user is locked.", $account->getId()));
        }
    }

    private function checkSenderConstraints(Tier $kyc, Group $sender_account){
        if ($kyc->getCode() != "KYC2") {
            throw new HttpException(
                400,
                sprintf("Sender (%s) KYC lower than KYC2.", $sender_account->getId()));
        }

        if (!$sender_account->getActive()) {
            throw new HttpException(
                400,
                printf("Account (%s) is not active.", $sender_account->getId()));
        }

        if (!$sender_account->getKycManager()->isEnabled()) {
            throw new HttpException(
                400,
                sprintf("Account (%s) user is not enabled.", $sender_account->getId()));
        }
        if (!$sender_account->getKycManager()->isAccountNonLocked()) {
            throw new HttpException(
                400,
                sprintf("Account (%s) user is locked.", $sender_account->getId()));
        }
    }
}