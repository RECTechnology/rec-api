<?php

namespace Test\FinancialApiBundle\Utils;

interface MongoDBTestInterface {
    function startMongo(): void;
    function stopMongo(): void;
}