<?php
/**
 * Created by PhpStorm.
 * User: iulian
 * Date: 1/02/19
 * Time: 15:35
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;


class NotificatorAggregator implements Notificator
{

    /** @var Notificator[] */
    private $notificators;

    /**
     * NotificatorAggregator constructor.
     * @param $notificators
     */
    public function __construct($notificators)
    {
        $this->notificators = $notificators;
    }

    function msg($msg)
    {
        /** @var Notificator $notificator */
        foreach ($this->notificators as $notificator) {
            $notificator->msg($msg);
        }
    }
}