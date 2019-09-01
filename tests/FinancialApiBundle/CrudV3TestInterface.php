<?php


namespace Test\FinancialApiBundle;


interface CrudV3TestInterface {
    function testIndex();
    function testExport();
    function testSearch();
    function testShow();
    function testCreate();
    function testUpdate();
    function testDelete();
}