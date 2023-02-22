<?php

namespace App\Controller;

use App\DependencyInjection\Commons\UploadManager;
use App\Exception\AppException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait ImportEntityTrait
 * @package App\Controller
 */
trait ImportEntityTrait {

    protected function importAction(Request $request, $role) {
        $this->checkPermissions($role, self::CRUD_CREATE);

        if(!$request->request->has('csv')) {
            throw new AppException(400, "'csv' parameter is required");
        }

        $location = $request->request->get('csv');

        /** @var UploadManager $fileManager */
        $fileManager = $this->get("file_manager");
        $contents = $fileManager->readFileUrl($location);
        $entities = $this->csvToArray($contents);

        $rowCount = 1;
        foreach ($entities as $entity){

            $req = new Request();
            $req->setMethod("POST");
            foreach ($entity as $field => $value){
                $req->request->set($field, $value);
            }

            /** @var Response $resp */
            $resp = $this->createAction($req, $role);
            if($resp->getStatusCode() !== Response::HTTP_CREATED){
                $respContent = json_decode($resp->getContent());
                throw new AppException(
                    400,
                    "Error in row {$rowCount}: {$respContent->message}",
                    $respContent->data
                );
            }
            $rowCount++;
        }

        return $this->rest(
            Response::HTTP_CREATED,
            "success",
            "Added {$rowCount} rows successfully"
        );
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
            else {
                throw new AppException(
                    Response::HTTP_BAD_REQUEST,
                    "Invalid CSV: csv file must contain at least the headers row"
                );
            }

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

}