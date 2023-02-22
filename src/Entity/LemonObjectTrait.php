<?php


namespace App\Entity;


trait LemonObjectTrait {

    use ExternalObjectTrait;

    /**
     * @return string
     */
    public function getLemonReference(): ?string
    {
        return $this->getExternalReference();
    }

    /**
     * @param string $lemon_reference
     */
    public function setLemonReference(string $lemon_reference): void
    {
        $this->setExternalReference($lemon_reference);
    }

}