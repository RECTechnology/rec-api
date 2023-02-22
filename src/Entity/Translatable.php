<?php

namespace App\Entity;

/**
 * Interface Localizable
 * @package App\Entity
 */
interface Translatable {
    function getLocale();
    function setLocale(string $locale);
}
