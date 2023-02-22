<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:26
 */

namespace App\DependencyInjection\Commons;

use App\Entity\Notification;

/**
 * Interface Notifier
 * @package App\DependencyInjection\Commons
 */
interface Notifier {

    /**
     * @param Notification $notification
     * @param callable $on_success
     * @param callable $on_failure
     * @param callable $on_finally
     */
    function send(Notification $notification, $on_success, $on_failure, $on_finally): void;
}