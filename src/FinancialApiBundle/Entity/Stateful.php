<?php

namespace App\FinancialApiBundle\Entity;

/**
 * Interface Stateful
 * @package App\FinancialApiBundle\Entity
 */
interface Stateful {
    const STATUS_CREATED = "created";
    const STATUS_APPROVED = "approved";
    const STATUS_PENDING_APPROVE = "pending_approve";
}