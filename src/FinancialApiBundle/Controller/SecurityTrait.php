<?php


namespace App\FinancialApiBundle\Controller;


use App\FinancialApiBundle\Exception\AppException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;

trait SecurityTrait
{

    /**
     * @param $result
     * @return array|null
     */
    protected function secureOutput($result) {
        if (method_exists($this, 'getSerializationContext')) {
            $ctx = $this->getSerializationContext();
        }
        else {
            $ctx = new SerializationContext();
            $ctx->enableMaxDepthChecks();
        }
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        return $serializer->toArray($result, $ctx);
    }

    /**
     * @param $entity
     */
    function validate($entity){
        $errors = $this->get('validator')->validate($entity);
        if(count($errors) > 0)
            throw new AppException(400, "Validation error", $errors);
    }
}