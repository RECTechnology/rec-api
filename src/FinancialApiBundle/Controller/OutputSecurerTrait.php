<?php


namespace App\FinancialApiBundle\Controller;


use JMS\Serializer\Serializer;

trait OutputSecurerTrait
{
    /**
     * @param $result
     * @return array|null
     */
    protected function secureOutput($result) {
        $ctx = $this->getSerializationContext();
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        return $serializer->toArray($result, $ctx);
    }
}