<?php


namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Exception\PreconditionFailedException;

interface PreDeleteChecks {
    /**
     * @throws PreconditionFailedException
     */
    function isDeleteAllowed();
}