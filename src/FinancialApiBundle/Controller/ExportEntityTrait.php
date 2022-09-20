<?php

namespace App\FinancialApiBundle\Controller;

use App\FinancialApiBundle\Entity\EmailExport;
use DateTimeZone;
use Exception;
use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Trait ExportEntityTrait
 * @package App\FinancialApiBundle\Controller
 */
trait ExportEntityTrait {

    /**
     * @param Request $request
     * @param $role
     * @return Response
     * @throws Exception
     */
    protected function exportAction(Request $request, $role) {
        $this->checkPermissions($role, self::CRUD_SEARCH);
        $request->query->set("limit", 2**31);
        $fieldMap = json_decode($request->query->get("field_map", "{}"), true);
        if(json_last_error()) throw new HttpException(400, "Bad field_map, it must be a valid JSON");
        [$total, $result] = $this->export($request);
        $elems = $this->secureOutput($result);

        $namer = new CamelCaseToSnakeCaseNameConverter(null, false);

        $fullClassNameParts = explode("\\", $this->getRepository()->getClassName());
        $className = $fullClassNameParts[count($fullClassNameParts) - 1];
        $underscoreName = $namer->normalize($className);
        $now = new \DateTime("now", new DateTimeZone('Europe/Madrid'));
        $dwFilename = "export-" .  $underscoreName . "s-" . $now->format('Y-m-d\TH-i-sO') . ".csv";

        $fs = new Filesystem();
        $tmpFilename = "/tmp/$dwFilename";
        $fs->touch($tmpFilename);
        $fp = fopen($tmpFilename, 'w');

        $export = [array_keys($fieldMap)];
        foreach($elems as $el){
            try {
                $obj = new JsonObject($el);
            } catch (InvalidJsonException $e) {
                throw new HttpException(400, "Invalid JSON: " . $e->getMessage(), $e);
            }
            $exportRow = [];
            foreach($fieldMap as $jsonPath){
                try {
                    $found = $obj->get($jsonPath);
                } catch (Exception $e) {
                    throw new HttpException(400, "Invalid JsonPath: " . $e->getMessage(), $e);
                }
                if(count($found) == 0)
                    $exportRow []= null;
                elseif(count($found) == 1) {
                    if(is_array($found[0])) {
                        throw new HttpException(
                            400,
                            "Error with JSONPath '$jsonPath': every field must return single value, it returns " . json_encode($found[0])
                        );
                    }
                    $exportRow []= $found[0];
                }
                else {
                    foreach($found as $v){
                        if(is_array($v)){
                            throw new HttpException(
                                400,
                                "Error with JSONPath '$jsonPath': every field must return single value, it returns " . json_encode($v)
                            );
                        }
                    }
                    $exportRow []= implode("|", $found);
                }
            }
            $export []= $exportRow;
        }

        foreach($export as $row){
            fputcsv($fp, $row, ";");
        }


        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $dwFilename . '"');
        $response->headers->set('Content-Length', filesize($tmpFilename));

        $response->setContent(file_get_contents($tmpFilename));
        $fs->remove($tmpFilename);
        return $response;
    }

    /**
     * @param Request $request
     * @param $role
     * @return Response
     * @throws Exception
     */
    protected function exportForEmailAction(Request $request) {
        $request->query->set("limit", 2**31);
        $fieldMap = json_decode($request->query->get("field_map", "{}"), true);
        if(json_last_error()) throw new HttpException(400, "Bad field_map, it must be a valid JSON");
        if(!$request->request->has('email')) throw new HttpException(400, "Email required");

        $fullClassNameParts = explode("\\", $this->getRepository()->getClassName());
        $className = $fullClassNameParts[count($fullClassNameParts) - 1];

        $emailExport = new EmailExport();
        $emailExport->setStatus(EmailExport::STATUS_CREATED);
        $emailExport->setEntityName($className);
        $emailExport->setFieldMap($fieldMap);
        $emailExport->setQuery($request->query->all());
        $emailExport->setEmail($request->request->get('email'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($emailExport);
        $em->flush();

    }

}