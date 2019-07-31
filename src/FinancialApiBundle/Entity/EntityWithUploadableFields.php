<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/19/19
 * Time: 6:24 PM
 */

namespace App\FinancialApiBundle\Entity;


interface EntityWithUploadableFields
{
    function getUploadableFields();
}