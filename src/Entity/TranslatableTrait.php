<?php

namespace App\Entity;

/**
 * Trait LocalizableTrait
 * @package App\Entity
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
