<?php

namespace App\FinancialApiBundle\Entity;

/**
 * Trait LocalizableTrait
 * @package App\FinancialApiBundle\Entity
 */
trait TranslatableTrait {

    /**
     * @var string
     */
    private $locale;

    /**
     * @return string|null
     */
    function getLocale() {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    function setLocale(string $locale){
        $this->locale = $locale;
    }
}
