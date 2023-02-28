<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 4/18/19
 * Time: 12:33 PM
 */

namespace App\Controller\Management\Admin;

use App\Controller\BaseApiController;
use App\Entity\DelegatedChange;
use App\Entity\DelegatedChangeData;
use App\Entity\Group;
use App\Entity\Tier;
use App\Entity\TransactionBlockLog;
use Doctrine\Common\Annotations\AnnotationException;
use FOS\RestBundle\Controller\Annotations as Rest;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;

/**
 * Class DelegatedChangeDataController
 * @package App\Controller\Management\Admin
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

        $log_handler = $this->container->get('net.app.commons.tx_block_log_handler');
        $log_text = 'Could not read csv: ';

        $fileSrc = $request->request->get('path');

        $this->isCsvFile($fileSrc);

        //$fileHandler = fopen($fileSrc, "r");
        //if(mime_content_type($fileHandler) !== "text/plain") throw new HttpException(400,"The file is not a CSV");

        $csv_raw_content = file_get_contents($fileSrc);
        $csv_content = str_replace('"','', $csv_raw_content);
        if(strlen(file_get_contents($fileSrc)) == 0) throw new HttpException(400,"The file not contain any data");

        $rows = explode(PHP_EOL, $csv_content);
        if (substr_count($rows[0], ',') > substr_count($rows[0], ';')){
            $separator = ',';
        }else{
            $separator = ';';
        }

        $fileHeaders = explode($separator, $rows[0]);
        $requiredHeaders = ["sender", "exchanger", "account", "amount"];

        foreach ($fileHeaders as $index=>$header){
            $fileHeaders[$index] = trim($header);
        }

        $logger = $this->get('manager.logger');
        $logger->info('file path: '. $fileSrc);
        $logger->info('content string: '. $csv_content);
        $logger->info('The all headers: <'.$rows[0].'> size is '. strlen($rows[0]));
        $logger->info('last header size: '. strlen($fileHeaders[3]));
        $logger->info('amount: <'. $fileHeaders[3].'>');
        $logger->info('content string: <'. $csv_content.'>');
        $logger->info('separator: '. $separator);
        $logger->info('rows size: '. strval(count($rows)));

        if($requiredHeaders != $fileHeaders) {
            $error_text = "Missing required headers";
            $log_handler->persistLog($dc, TransactionBlockLog::TYPE_ERROR, $log_text.$error_text);
            throw new HttpException(400, $error_text);
        }

        $firstRow = explode($separator, $rows[1]);
        if(count($firstRow) != 4) {
            $error_text = "No valid data found";
            $log_handler->persistLog($dc, TransactionBlockLog::TYPE_ERROR, $log_text.$error_text);
            throw new HttpException(400, $error_text);
        }

        $log_text = sprintf('From %s to %s. The csv data is pending to validate', $dc->getStatus(),
            DelegatedChange::STATUS_PENDING_VALIDATION);
        $log_handler->persistLog($dc, TransactionBlockLog::TYPE_DEBUG, $log_text);

        $dc->setUrlCsv($request->request->get('path'));
        $dc->setStatus(DelegatedChange::STATUS_PENDING_VALIDATION);
        $em->flush();

        $log_handler->persistLog($dc, TransactionBlockLog::TYPE_DEBUG, 'csv saved successfully');

        return $this->rest(201, "success", "CSV added successfully");

    }


    public function getRepositoryName() {
        return "FinancialApiBundle:DelegatedChangeData";
    }

    function getNewEntity() {
        return new DelegatedChangeData();
    }

    private function isCsvFile($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);

        # get the content type
        $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if($type !== 'text/plain'){
            throw new HttpException(400,"The file is not a CSV");
        }
    }
}