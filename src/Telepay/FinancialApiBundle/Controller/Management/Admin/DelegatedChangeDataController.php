<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use AssertionError;
use DateTime;
use Doctrine\Common\Annotations\AnnotationException;
use FOS\RestBundle\Controller\Annotations as Rest;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Telepay\FinancialApiBundle\Entity\DelegatedChangeData;

/**
 * Class DelegatedChangeDataController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class DelegatedChangeDataController extends BaseApiController{

    const DELEGATED_CHANGE_CSV_HEADERS = ["account", "exchanger", "amount", "pan", "expiry_year", "expiry_month", "cvv2"];

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
    public function loadCsvAction(Request $request){

        if(!$request->request->has('path'))
            throw new HttpException(400, "path is required");

        $fileSrc = $request->request->get('path');
        $request->request->remove('path');

        /** @var UploadManager $fileManager */
        $fileManager = $this->get("file_manager");

        $csvContents = $fileManager->readFileUrl($fileSrc);

        $contents = $this->csvToArray($csvContents);

        foreach(static::DELEGATED_CHANGE_CSV_HEADERS as $hdr){
            if(!array_key_exists($hdr, $contents[0])){
                $hdrStr = implode(", ", static::DELEGATED_CHANGE_CSV_HEADERS);
                throw new HttpException(
                    400,
                    "CSV format error: header '$hdr' not found: CSV file must contain the following headers: $hdrStr"
                );
            }
        }

        $accRepo = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:Group");

        try{
            $rowCount = 1;
            foreach ($contents as $dcdArray){
                $account = $accRepo->findOneBy(["cif" => $dcdArray['account']]);
                if(!$account) throw new HttpException(
                    400,
                    "Invalid account ID: the csv 'account' value must be the 'cif' of the user account."
                );

                $exchanger = $accRepo->findOneBy(["cif" => $dcdArray['exchanger']]);
                if(!$exchanger) throw new HttpException(
                    400,
                    "Invalid exchanger ID: the csv 'exchanger' value must be the 'cif' of the exchanger account."
                );

                $req = new Request();
                $req->setMethod("POST");
                $req->request->set('account_id', $account->getId());
                $req->request->set('exchanger_id', $exchanger->getId());
                $req->request->set('amount', $dcdArray["amount"]);
                $req->request->set('pan', $dcdArray["pan"]);
                $req->request->set('expiry_date', $dcdArray["expiry_month"] . "/" . $dcdArray["expiry_year"]);
                $req->request->set('cvv2', $dcdArray["cvv2"]);

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
        if (($handle = fopen($tmpLocation, "r")) !== false) {
            if(($row = fgetcsv($handle)) !== false) {
                $headers = [];
                foreach ($row as $hdr) {
                    $headers []= trim($hdr);
                }
            }
            else throw new HttpException(400, "Invalid CSV: csv file must contain at least the headers row");

            while(($row = fgetcsv($handle)) !== false) {
                $rowArr = [];
                for($i=0; $i<count($row); $i++){
                    $rowArr[$headers[$i]] = $row[$i];
                }

                $contents []= $rowArr;
            }
        }
        fclose($handle);
        unlink($tmpLocation);
        return $contents;
    }


    function getRepositoryName() {
        return "TelepayFinancialApiBundle:DelegatedChangeData";
    }

    function getNewEntity() {
        return new DelegatedChangeData();
    }
}