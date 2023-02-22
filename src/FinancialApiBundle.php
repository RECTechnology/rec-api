<?php

namespace App;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\Security\Factory\SignatureSecurityFactory;

class FinancialApiBundle extends Bundle {
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SignatureSecurityFactory());
    }
}
