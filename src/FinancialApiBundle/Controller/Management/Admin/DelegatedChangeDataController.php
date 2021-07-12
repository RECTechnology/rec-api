<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Admin;

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

        if(!$request->request->has('path'))
            throw new HttpException(400, "path is required");

        $fileSrc = $request->request->get('path');
        $request->request->remove('path');

        /** @var UploadManager $fileManager */
        $fileManager = $this->get("file_manager");

        if ($request->server->get("REMOTE_ADDR") == "127.0.0.1"){
            $csvContents = file_get_contents($fileSrc);
        }else{
            $csvContents = $fileManager->readFileUrl($fileSrc);
        }
        $contents = $this->csvToArray($csvContents);
        //delegated change csv
        if(array_key_exists('creditcard', $contents[0])) {

            if(array_key_exists('sender', $contents[0])) {
                throw new HttpException(400, "only one of creditcard and sender value are allowed");
            }
            $hdrStr = implode(", ", static::DELEGATED_CHANGE_CSV_HEADERS);
            foreach (static::DELEGATED_CHANGE_CSV_HEADERS as $csvHeader) {
                if (!array_key_exists($csvHeader, $contents[0])) {
                    throw new HttpException(
                        400,
                        "CSV format error: header '$csvHeader' not found: CSV file must contain the following headers: $hdrStr"
                    );
                }
            }

            $accRepo = $this->getDoctrine()->getRepository(Group::class);

            try {
                foreach ($contents as $dcdArray) {  // check constraints
                    /** @var Group $account */
                    $account = $accRepo->findOneBy(["id" => $dcdArray['account']]);
                    if (!$account) throw new HttpException(
                        400,
                        "Invalid account ID: the csv 'account' value must be the 'id' of the user account (was not found in accounts)."
                    );

                    /** @var Group $exchanger */
                    $exchanger = $accRepo->findOneBy(["id" => $dcdArray['exchanger']]);
                    if (!$exchanger) throw new HttpException(
                        400,
                        "Invalid exchanger ID: the csv 'exchanger' value must be the 'id' of the exchanger account (was not found in exchangers)."
                    );
                    $kyc_repo = $this->getDoctrine()->getRepository(Tier::class);
                    /** @var Tier $kyc */
                    $kyc = $kyc_repo->findOneBy(['id' => $exchanger->getLevel()]);

                    $this->checkExchangerConstraints($kyc, $exchanger);

                    $this->checkAccountConstraints($account);
                }
                $rowCount = 0;
                foreach ($contents as $dcdArray) {
                    $account = $accRepo->findOneBy(["id" => $dcdArray['account']]);
                    $exchanger = $accRepo->findOneBy(["id" => $dcdArray['exchanger']]);

                    $req = new Request();
                    $req->setMethod("POST");
                    $req->request->set('delegated_change_id', $request->request->get('delegated_change_id'));
                    $req->request->set('account_id', $account->getId());
                    $req->request->set('exchanger_id', $exchanger->getId());
                    $req->request->set('amount', $dcdArray["amount"]);
                    $req->request->set('creditcard_id', $dcdArray["creditcard"]);

                    /** @var Response $resp */
                    $resp = $this->createAction($req);
                    if ($resp->getStatusCode() !== BaseApiController::HTTP_STATUS_CODE_CREATED) {
                        $respContent = json_decode($resp->getContent(), JSON_OBJECT_AS_ARRAY);
                        return $this->restV2(
                            400,
                            "error",
                            "Error in row " . $rowCount . ": " . $respContent['message'], $respContent['data']
                        );

                    }
                    $rowCount++;
                }
            } catch (HttpException $e) {
                return $this->restV2(
                    $e->getStatusCode(),
                    "error",
                    "Error in row " . $rowCount . ": " . $e->getMessage()
                );
            }

            return $this->restV2(201, "success", "Added " . $rowCount . " rows successfully");

        //massive transactions csv
        }elseif(array_key_exists('sender', $contents[0])) {
            $hdrStr = implode(", ", static::MASSIVE_TRANSACTIONS_CSV_HEADERS);
            foreach (static::MASSIVE_TRANSACTIONS_CSV_HEADERS as $csvHeader) {
                if (!array_key_exists($csvHeader, $contents[0])) {
                    throw new HttpException(
                        400,
                        "CSV format error: header '$csvHeader' not found: CSV file must contain the following headers: $hdrStr"
                    );
                }
            }

            $accRepo = $this->getDoctrine()->getRepository(Group::class);

            try {
                foreach ($contents as $dcdArray) {  // check constraints
                    /** @var Group $account */
                    $account = $accRepo->findOneBy(["id" => $dcdArray['account']]);
                    if (!$account) throw new HttpException(
                        400,
                        "Invalid account ID: the csv 'account' value must be the 'id' of the user account (was not found in accounts)."
                    );

                    /** @var Group $sender_account */
                    $sender_account = $accRepo->findOneBy(["id" => $dcdArray['sender']]);
                    if (!$sender_account) throw new HttpException(
                        400,
                        "Invalid account ID: the csv 'sender' value must be the 'id' of the user account (was not found in accounts)."
                    );

                    if (array_key_exists('exchanger', $dcdArray)) {
                        /** @var Group $exchanger */
                        $exchanger = $accRepo->findOneBy(["id" => $dcdArray['exchanger']]);

                        if (!$exchanger) {
                            throw new HttpException(
                                400,
                                sprintf("Exchanger (%s) not found.", $dcdArray['exchanger']));
                        }

                        $kyc_repo = $this->getDoctrine()->getRepository(Tier::class);
                        /** @var Tier $kyc */
                        $kyc = $kyc_repo->findOneBy(['id' => $exchanger->getLevel()]);

                        $this->checkExchangerConstraints($kyc,$exchanger);

                    }

                    $this->checkAccountConstraints($account);

                    /** @var Tier $kyc */
                    $kyc = $kyc_repo->findOneBy(['id' => $sender_account->getLevel()]);

                    $this->checkSenderConstraints($kyc, $sender_account);

                }
                $rowCount = 0;
                foreach ($contents as $dcdArray) {
                    $account = $accRepo->findOneBy(["id" => $dcdArray['account']]);

                    $req = new Request();
                    $req->setMethod("POST");
                    $req->request->set('delegated_change_id', $request->request->get('delegated_change_id'));
                    $req->request->set('account_id', $account->getId());
                    $req->request->set('sender_id', $sender_account->getId());
                    if (array_key_exists('exchanger', $dcdArray)) {
                        $exchanger = $accRepo->findOneBy(["id" => $dcdArray['exchanger']]);
                        $req->request->set('exchanger_id', $exchanger->getId());
                    }
                    $req->request->set('amount', $dcdArray["amount"]);

                    /** @var Response $resp */
                    $resp = $this->createAction($req);
                    if ($resp->getStatusCode() !== BaseApiController::HTTP_STATUS_CODE_CREATED) {
                        $respContent = json_decode($resp->getContent(), JSON_OBJECT_AS_ARRAY);
                        return $this->restV2(
                            400,
                            "error",
                            "Error in row " . $rowCount . ": " . $respContent['message'], $respContent['data']
                        );

                    }
                    $rowCount++;
                }
            } catch (HttpException $e) {
                return $this->restV2(
                    $e->getStatusCode(),
                    "error",
                    "Error in row " . $rowCount . ": " . $e->getMessage()
                );
            }

            return $this->restV2(201, "success", "Added " . $rowCount . " rows successfully");
        }else{
            throw new HttpException(400, "creditcard or sender value are required");
        }

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