<?php

namespace Arbaf\FinancialApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ArbafFinancialApiBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
