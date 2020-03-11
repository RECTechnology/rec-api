<?php


namespace App\FinancialApiBundle\Controller;


use App\FinancialApiBundle\Entity\Group;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trait OutputSecurerTrait
{

    /**
     * @param $result
     * @return array|null
     */
    protected function secureOutput($result) {
        if (method_exists($this, 'getSerializationContext'))
            $ctx = $this->getSerializationContext();
        else
            $ctx = new SerializationContext();
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        return $serializer->toArray($result, $ctx);
    }
}