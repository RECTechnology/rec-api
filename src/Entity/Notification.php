<?php


namespace App\Entity;


interface Notification {
    function getUrl();
    function getContent();
}