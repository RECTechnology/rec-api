<?php

namespace App\FinancialApiBundle\Entity;

/**
 * Interface Localizable
 * @package App\FinancialApiBundle\Entity
 */
interface Localizable {
    function getTranslatableLocale();
    function setTranslatableLocale(string $locale);
}
