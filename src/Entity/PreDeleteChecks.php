<?php


namespace App\Entity;


use App\Exception\PreconditionFailedException;

interface PreDeleteChecks {
    /**
     * @throws PreconditionFailedException
     */
    function isDeleteAllowed();
}