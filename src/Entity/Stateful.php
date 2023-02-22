<?php

namespace App\Entity;

/**
 * Interface Stateful
 * @package App\Entity
 */
interface Stateful {
    const STATUS_CREATED = "created";
    const STATUS_UPLOADED = "uploaded";
    const STATUS_APPROVED = "approved";
    const STATUS_DECLINED = "declined";
    const STATUS_ARCHIVED = "archived";
    const STATUS_SUBMITTED = "submitted";

    function skipStatusChecks($skip = true): void;
    function statusChecksSkipped(): bool;
}