<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 */
class EmailExport extends AppObject
{
    public const STATUS_CREATED = "created";
    public const STATUS_PROCESSING = "processing";
    public const STATUS_SUCCESS = "success";
    public const STATUS_FAILED = "failed";
    public const STATUS_ERROR = "error";

    /**
     * @ORM\Column(type="string")
     * @Groups({"admin"})
     */
    private $status;

    /**
     * @ORM\Column(type="string")
     * @Groups({"admin"})
     */
    private $entity_name;

    /**
     * @ORM\Column(type="array")
     * @Groups({"admin"})
     */
    private $field_map;

    /**
     * @ORM\Column(type="array")
     * @Groups({"admin"})
     */
    private $query;

    /**
     * @ORM\Column(type="string")
     * @Groups({"admin"})
     */
    private $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"admin"})
     */
    private $last_error;

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getEntityName()
    {
        return $this->entity_name;
    }

    /**
     * @param mixed $entity_name
     */
    public function setEntityName($entity_name): void
    {
        $this->entity_name = $entity_name;
    }

    /**
     * @return mixed
     */
    public function getFieldMap()
    {
        return $this->field_map;
    }

    /**
     * @param mixed $field_map
     */
    public function setFieldMap($field_map): void
    {
        $this->field_map = $field_map;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     */
    public function setQuery($query): void
    {
        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * @param mixed $last_error
     */
    public function setLastError($last_error): void
    {
        $this->last_error = $last_error;
    }

}