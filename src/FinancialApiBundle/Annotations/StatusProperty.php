<?php

namespace App\FinancialApiBundle\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class TranslatedProperty
 * @package App\FinancialApiBundle\Annotations
 * @Annotation
 * @Target({"PROPERTY"})
 */
class StatusProperty {

    /** @var array $choices */
    private $choices;

    /** string $initial */
    private $initial;

    /**
     * Status constructor.
     * @param array $args
     */
    public function __construct(array $args) {
        $this->choices = $args['choices'];
        $this->initial = $args['initial'];
    }

    /**
     * @param $status
     * @return bool
     */
    public function isFinal($status){
        return in_array('final', $this->choices[$status]) && $this->choices[$status]['final'];
    }

    /**
     * @return string
     */
    public function getInitialStatus(){
        if($this->initial != null) return $this->initial;
        return array_keys($this->choices)[0];
    }

    /**
     * @param $oldStatus
     * @param $newStatus
     * @return bool
     */
    public function isStatusChangeAllowed($oldStatus, $newStatus){
        return isset($this->choices[$oldStatus]['to']) && in_array($newStatus, $this->choices[$oldStatus]['to']);
    }

    /**
     * example annotation
     * Status(choices={
     *          "created" = {"to" = {"ready"}},
     *          "ready"= {"final"=false, "to" = {"created", "cancelled"}},
     *          "processed"= {"final"=true, "to" = {}},
     *          "cancelled"= {"final"=true},
     *          "failed"={"final"=true},
     *      }, initial = "created"
     * )
     */

}