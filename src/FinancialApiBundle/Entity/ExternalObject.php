<?php


namespace App\FinancialApiBundle\Entity;


interface ExternalObject {

    /**
     * @return string
     */
    public function getExternalReference(): ?string;

    /**
     * @return array
     * Returns the raw object stored in the external provider
     */
    public function getExternalInfo(): ?array;

}