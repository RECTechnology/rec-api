<?php


namespace App\FinancialApiBundle\Controller;


use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;

trait OutputSecurerTrait
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
}