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
     * @return SerializationContext
     */
    private function getSerializationContext() {

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');

        /** @var AuthorizationCheckerInterface $auth */
        $auth = $this->get('security.authorization_checker');

        $grantsMap = [
            self::ROLE_ROOT => Group::SERIALIZATION_GROUPS_ROOT,
            self::ROLE_SUPER_ADMIN => Group::SERIALIZATION_GROUPS_ADMIN,
            self::ROLE_SUPER_MANAGER => Group::SERIALIZATION_GROUPS_MANAGER,
            self::ROLE_SUPER_USER => Group::SERIALIZATION_GROUPS_USER,
            'IS_AUTHENTICATED_ANONYMOUSLY' => Group::SERIALIZATION_GROUPS_PUBLIC,
        ];

        $ctx = new SerializationContext();
        $ctx->enableMaxDepthChecks();
        if($tokenStorage->getToken()){
            foreach($grantsMap as $grant => $serializationGroup){
                if($auth->isGranted($grant)) {
                    $ctx->setGroups($serializationGroup);
                    return $ctx;
                }
            }
        }

        $ctx->setGroups(Group::SERIALIZATION_GROUPS_PUBLIC);
        return $ctx;
    }

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