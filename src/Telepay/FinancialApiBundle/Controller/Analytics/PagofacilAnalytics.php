<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 8/28/14
 * Time: 6:36 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Analytics;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;

class PagofacilAnalytics extends BaseAnalytics{

    public function getServiceName(){
        return 'PagoFacil';
    }

    public function transactions(Request $request, $mode = true) {
        return parent::transactions($request, $mode);
    }

    public function transactionsTest(Request $request, $mode = false) {
        return parent::transactionsTest($request, $mode);
    }

    public function stats(Request $request, $mode = true) {
        return parent::stats($request, $mode);
    }

}