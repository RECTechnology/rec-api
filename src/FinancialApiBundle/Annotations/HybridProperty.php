<?php

namespace App\FinancialApiBundle\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class HybridPropery
 * @package App\FinancialApiBundle\Annotations
 * @Annotation
 * @Target({"PROPERTY"})
 */
class HybridProperty {

    const DEFAULT_MANAGER = "doctrine.odm.mongodb.document_manager";

    /** @var string $identifier */
    private $identifier;

    /** @var string $targetEntity */
    private $targetEntity;

    /** @var string $manager */
    private $manager;

    /**
     * Status constructor.
     * @param array $args
     */
    public function __construct(array $args) {
        $this->identifier = $args['identifier'];
        $this->targetEntity = $args['targetEntity'];
        $this->manager = isset($args['manager'])? $args['manager']: self::DEFAULT_MANAGER;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getManager(): string
    {
        return $this->manager;
    }

    /**
     * @return string
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }
}