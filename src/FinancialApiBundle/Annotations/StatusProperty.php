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

    /** @var array $initial_statuses */
    private $initial_statuses;

    /**
     * Status constructor.
     * @param array $args
     */
    public function __construct(array $args) {
        $this->choices = $args['choices'];
        if(in_array('initial_statuses', array_keys($args)))
            $this->initial_statuses = $args['initial_statuses'];
    }

    /**
     * @param $status
     * @return bool
     */
    public function isFinal($status){
        return in_array('final', $this->choices[$status]) && $this->choices[$status]['final'];
    }

    /**
     * @return array
     */
    public function getInitialStatuses(){
        if($this->initial_statuses != null) return $this->initial_statuses;
        return array_keys($this->choices);
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
     *          "created"={"to"={"ready"}},
     *          "ready"={"final"=false, "to"={"created", "cancelled"}},
     *          "processed"={"final"=true, "to"={}},
     *          "cancelled"={"final"=true},
     *          "failed"={"final"=true},
     *      },
     *      initial_statuses={"created"}
     * )
     */

}