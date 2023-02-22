<?php

namespace App\Event;

use App\Entity\Group;
use Symfony\Component\EventDispatcher\Event;

class MintNftEvent extends BaseNftEvent
{

    const NAME = 'nft.mint';
}