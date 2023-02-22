<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:35
 */

namespace App\DependencyInjection\Commons;

/**
 * Class NotificatorAggregator
 * @package App\DependencyInjection\Commons
 */
class NotificatorAggregator implements Messenger
{

    /** @var Notifier[] $notificators */
    private $notificators;

    /**
     * NotificatorAggregator constructor.
     * @param $notificators
     */
    public function __construct($notificators)
    {
        $this->notificators = $notificators;
    }

    function send($msg)
    {
        /** @var Notifier $notificator */
        foreach ($this->notificators as $notificator) {
            $notificator->send($msg);
        }
    }
}