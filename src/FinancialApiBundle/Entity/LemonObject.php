<?php


namespace App\FinancialApiBundle\Entity;


interface LemonObject extends ExternalObject {

    /**
     * @return string
     */
    public function getLemonReference(): ?string;

    /**
     * @return integer
     */
    public function getLemonStatus(): int;

}