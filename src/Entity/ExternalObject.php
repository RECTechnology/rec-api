<?php


namespace App\Entity;


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

    /**
     * @return bool
     * Returns if the object is auto-fetched from the provider
     */
    public function isAutoFetched(): bool;

}