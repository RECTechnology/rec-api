<?php

namespace App\FinancialApiBundle\Financial;

class Currency {
    public static $REC = "REC";
    public static $EUR = "EUR";

    public static $ALL = array("REC");
    public static $ALL_COMPLETED = array("REC", "EUR");

    public static $SCALE = array(
        "REC" => 8,
        "ROSA" => 8,
        "EUR" => 2
    );
}