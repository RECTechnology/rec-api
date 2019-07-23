<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 8:59 PM
 */

namespace Telepay\FinancialApiBundle\Response;

use JMS\Serializer\Annotation\XmlKeyValuePairs;

class ApiResponseV2 {

    /** @XmlKeyValuePairs */
    public $data;

    public $status;

    public $message;

    /**
     * ApiResponse constructor.
     * @param $status
     * @param $data
     */
    public function __construct($status, $message, $data)
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }

}
