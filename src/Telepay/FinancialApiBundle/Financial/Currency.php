<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/15/15
 * Time: 3:59 PM
 */

namespace Telepay\FinancialApiBundle\Financial;

class Currency {
    public static $BTC = "BTC";
    public static $EUR = "EUR";
    public static $USD = "USD";
    public static $FAC = "FAC";
    public static $FAIRP = "FAIRP";
    public static $MXN = "MXN";
    public static $PLN = "PLN";
    public static $LISTA = array("BTC","EUR","USD","FAC","MXN","PLN");
    public static $ALL = array("BTC","EUR","USD","FAC","MXN","PLN");
    public static $TICKER_LIST = array("BTC","EUR","USD","FAC","MXN","PLN","FAIRP");
    public static $SCALE = array(
        "BTC" => 8,
        "EUR" => 2,
        "USD" => 2,
        "FAC" => 8,
        "FAIRP" => 8,
        "MXN" => 2,
        "PLN" => 2
    );
}