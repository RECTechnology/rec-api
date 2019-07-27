<?php

namespace App\FinancialApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\FinancialApiBundle\Security\Factory\SignatureSecurityFactory;

class FinancialApiBundle extends Bundle {

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
