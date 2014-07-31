<?php

namespace Arbaf\FinancialApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Arbaf\FinancialApiBundle\Security\Factory\SignatureSecurityFactory;
use Arbaf\FinancialApiBundle\Security\Factory\IPSecurityFactory;

class ArbafFinancialApiBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SignatureSecurityFactory());
        $extension->addSecurityListenerFactory(new IPSecurityFactory());
    }
}
