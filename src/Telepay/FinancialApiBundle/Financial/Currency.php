<?php

namespace Telepay\FinancialApiBundle\Financial;

class Currency {
    public static $REC = "REC";
    public static $EUR = "EUR";

    public static $ALL = array("REC");

    public static $SCALE = array(
        "REC" => 8,
        "EUR" => 2
    );
}