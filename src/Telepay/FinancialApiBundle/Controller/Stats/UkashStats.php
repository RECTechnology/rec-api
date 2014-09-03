<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/28/14
 * Time: 6:36 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Stats;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;

class UkashStats extends BaseStats{

    public function getServiceName(){
        return 'Ukash';
    }

    public function transactions(Request $request, $mode = true) {
        return parent::transactions($request, $mode);
    }

     public function transactionsTest(Request $request, $mode = false) {
         return parent::transactionsTest($request, $mode);
     }

    public function stats(Request $request, $mode = true) {
        parent::stats($request, $mode);
    }

}