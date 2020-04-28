<?php


namespace App\FinancialApiBundle\Entity;


interface Notification {
    function getUrl();
    function getContent();
}