<?php


namespace App\Entity;


interface LemonObject extends ExternalObject {

    /**
     * @return string
     */
    public function getLemonReference(): ?string;

}