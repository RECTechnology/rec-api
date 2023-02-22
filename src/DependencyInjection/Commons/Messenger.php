<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:26
 */

namespace App\DependencyInjection\Commons;


interface Messenger {
    function send($msg);
}