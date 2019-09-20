<?php

namespace App\FinancialApiBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;

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

    /**
     * @Groups({"manager"})
     */
    private $translations;

    function getTranslatableLocale(){
        return $this->locale;
    }

    function setTranslatableLocale(string $locale){
        $this->locale = $locale;
    }


    public function setTranslations(array $translations){
        $this->translations = $translations;
    }

    /**
     * @VirtualProperty(name="translations")
     * @Serializer\Type(name="array")
     * @Groups({"manager"})
     */
    public function getTranslations(){
        return $this->translations;
    }

}
