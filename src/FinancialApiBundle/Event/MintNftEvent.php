<?php

namespace App\FinancialApiBundle\Event;

use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\EventDispatcher\Event;

class MintNftEvent extends BaseNftEvent
{

    const NAME = 'nft.mint';
}