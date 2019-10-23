<?php

namespace App\FinancialApiBundle\Entity;

/**
 * Interface Localizable
 * @package App\FinancialApiBundle\Entity
 */
interface Translatable {
    function getLocale(): string;
    function setLocale(string $locale);
}
