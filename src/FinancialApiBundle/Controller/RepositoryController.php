<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/31/14
 * Time: 2:38 AM
 */


namespace App\FinancialApiBundle\Controller;


interface RepositoryController{
    function getRepositoryName();
    function getNewEntity();
}