<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Admin;

use AssertionError;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
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
        "pan",
        "expiry_year",
        "expiry_month",
        "cvv2",
        "creditcard_id"
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

        $csvContents = $fileManager->readFileUrl($fileSrc);

        $contents = $this->csvToArray($csvContents);

        $hdrStr = implode(", ", static::DELEGATED_CHANGE_CSV_HEADERS);
        foreach(static::DELEGATED_CHANGE_CSV_HEADERS as $csvHeader){
            if(!array_key_exists($csvHeader, $contents[0])){
                throw new HttpException(
                    400,
                    "CSV format error: header '$csvHeader' not found: CSV file must contain the following headers: $hdrStr"
                );
            }
        }

        $accRepo = $this->getDoctrine()->getRepository(Group::class);

        try{
            $rowCount = 1;
            foreach ($contents as $dcdArray){
                $account = $accRepo->findOneBy(["cif" => $dcdArray['account']]);
                if(!$account) throw new HttpException(
                    400,
                    "Invalid account ID: the csv 'account' value must be the 'cif' of the user account (was not found in accounts)."
                );

                $exchanger = $accRepo->findOneBy(["cif" => $dcdArray['exchanger']]);
                if(!$exchanger) throw new HttpException(
                    400,
                    "Invalid exchanger ID: the csv 'exchanger' value must be the 'cif' of the exchanger account (was not found in exchangers)."
                );

                $req = new Request();
                $req->setMethod("POST");
                $req->request->set('delegated_change_id', $request->request->get('delegated_change_id'));
                $req->request->set('account_id', $account->getId());
                $req->request->set('exchanger_id', $exchanger->getId());
                $req->request->set('amount', $dcdArray["amount"]);
                $req->request->set('pan', $dcdArray["pan"]);
                $req->request->set('expiry_date', $dcdArray["expiry_month"] . "/" . $dcdArray["expiry_year"]);
                $req->request->set('cvv2', $dcdArray["cvv2"]);
                $req->request->set('creditcard_id', $dcdArray["creditcard_id"]);

                /** @var Response $resp */
                $resp = $this->createAction($req);
                if($resp->getStatusCode() !== BaseApiController::HTTP_STATUS_CODE_CREATED){
                    $respContent = json_decode($resp->getContent(), JSON_OBJECT_AS_ARRAY);
                    return $this->restV2(
                        400,
                        "error",
                        "Error in row " . $rowCount . ": " . $respContent['message'], $respContent['data']
                    );

                }
                $rowCount++;
            }
        } catch (HttpException $e){
            return $this->restV2(
                $e->getStatusCode(),
                "error",
                "Error in row " . $rowCount . ": " . $e->getMessage()
            );
        }

        return $this->restV2(200,"success", "Added " . $rowCount . " rows successfully");
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
}