<?php

namespace Telepay\Bundle\ServicesBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TelepayServicesBundle extends Bundle {
    public function getParent() {
        return 'FOSUserBundle';
    }
}
