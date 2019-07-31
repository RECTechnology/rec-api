<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:35
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

/**
 * Class NotificatorAggregator
 * @package App\FinancialApiBundle\DependencyInjection\App\Commons
 */
class NotificatorAggregator implements Notificator
{

    /** @var Notificator[] $notificators */
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
        /** @var Notificator $notificator */
        foreach ($this->notificators as $notificator) {
            $notificator->send($msg);
        }
    }
}