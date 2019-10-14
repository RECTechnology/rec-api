<?php


namespace App\FinancialApiBundle\Entity;


interface MigratingEntity {
    static function getMigrationVersion();
    static function getOldEntity();
}