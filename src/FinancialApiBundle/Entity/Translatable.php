<?php

namespace App\FinancialApiBundle\Entity;

/**
 * Interface Localizable
 * @package App\FinancialApiBundle\Entity
 */
interface Translatable {
    function getLocale();
    function setLocale(string $locale);
}
