<?php

namespace App\FinancialApiBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Trait LocalizableTrait
 * @package App\FinancialApiBundle\Entity
 */
trait LocalizableTrait {

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    function getTranslatableLocale(){
        return $this->locale;
    }

    function setTranslatableLocale(string $locale){
        $this->locale = $locale;
    }


}
