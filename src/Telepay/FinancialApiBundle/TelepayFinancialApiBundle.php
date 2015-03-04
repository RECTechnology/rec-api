<?php

namespace Telepay\FinancialApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Telepay\FinancialApiBundle\Security\Factory\SignatureSecurityFactory;
use Telepay\FinancialApiBundle\Security\Factory\IPSecurityFactory;

class TelepayFinancialApiBundle extends Bundle {

    /**
     * ApiDoc Definitions
     *
     * @apiDefine UnauthorizedError
     * @apiErrorExample Unauthorized
     *    Error 401: Unauthorized
     *    {
     *          "code": 401,
     *          "message": "You are not authenticated"
     *    }
     *
     *
     */














    public function getParent()
    {
        return 'FOSUserBundle';
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SignatureSecurityFactory());
    }
}
