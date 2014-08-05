<?php

namespace Telepay\FinancialApiBundle\Controller\Services;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TestTestService
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
class TestTestService extends TestService
{
    public function test(Request $request) {
        $request->request->set('mode','T');
        return parent::test($request);
    }

}
