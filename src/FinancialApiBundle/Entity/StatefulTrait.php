<?php

namespace App\FinancialApiBundle\Entity;

/**
 * Trait StatefulTrait
 * @package App\FinancialApiBundle\Entity
 */
trait StatefulTrait {

    private $skip_status_checks = false;

    /**
     * @param bool $skip
     */
    public function skipStatusChecks($skip = true): void
    {
        $this->skip_status_checks = $skip;
    }

    /**
     * @return bool
     */
    public function statusChecksSkipped(): bool {
        return $this->skip_status_checks;
    }
}