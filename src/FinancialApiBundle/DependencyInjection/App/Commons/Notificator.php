<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:26
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\Notification;

/**
 * Interface Notificator
 * @package App\FinancialApiBundle\DependencyInjection\App\Commons
 */
interface Notificator {
    function send(Notification $notification, $success, $failure);
}