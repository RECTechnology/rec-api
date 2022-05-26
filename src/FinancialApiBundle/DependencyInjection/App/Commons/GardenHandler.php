<?php

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

class GardenHandler
{

    private $doctrine;

    public const ACTION_MAKE_REVIEW = 'make_review';
    public const ACTION_RECHARGE = 'recharge';
    public const ACTION_BUY = 'buy';


    public function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function updateGarden($action): void
    {
        switch ($action){
            case self::ACTION_MAKE_REVIEW:
                //do some stuff
                break;
            case self::ACTION_BUY:
                // do some stuff
                break;
            case  self::ACTION_RECHARGE:
                //do some stufff
                break;
            default:
                break;
        }
    }

    private function getEntityManager(){
        return $this->doctrine->getManager();
    }

}