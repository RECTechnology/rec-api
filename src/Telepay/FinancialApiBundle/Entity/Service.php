<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

class Service {

    public static $SERVICE_TEST = 0;
    public static $SERVICE_TODITOCASH = 1;
    public static $SERVICE_NETPAY = 2;
    public static $SERVICE_UKASH = 3;
    public static $SERVICE_HALCASH = 4;
    public static $SERVICE_PAYSAFECARD = 5;
    public static $SERVICE_UPAY = 6;
    public static $SERVICE_PAGOFACIL = 7;

    public static $MODE_PRODUCTION = 1;
    public static $MODE_TEST = 0;

}