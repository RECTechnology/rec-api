<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user_user_group")
 * @ExclusionPolicy("all")
 */
class UserGroup{
    const ROLE_DEFAULT = 'ROLE_READONLY';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @Serializer\MaxDepth(1)
     */
    private $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     * @Serializer\MaxDepth(1)
     */
    private $group;

    /**
     * @ORM\Column(type="text")
     */
    protected $roles;

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    public function addRole($role)
    {
        $roles = unserialize($this->roles);
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
        }
        $this->roles = serialize($roles);
        return $this;
    }

    /**
     * Returns the user roles
     *
     * @return array The roles
     */
    public function getRoles()
    {
        $roles = unserialize($this->roles);
        $group = $this->getGroup();
        $roles = array_merge($roles, $group->getRoles());

        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;

        usort($roles, function ($a, $b)
        {
            $roles_values = array(
                "ROLE_SUPER_ADMIN" => 0,
                "ROLE_ADMIN" => 1,
                "ROLE_WORKER" => 2,
                "ROLE_READONLY" => 3,
                "ROLE_KYC" => 4
            );
            $value_a =isset($roles_values[$a])?$roles_values[$a]:10;
            $value_b =isset($roles_values[$b])?$roles_values[$b]:10;

            if ($value_a == $value_b) {
                return 0;
            }
            return ($value_a < $value_b) ? -1 : 1;
        });

        return array_unique($roles);
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isSuperAdmin()
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    public function removeRole($role)
    {
        $roles = unserialize($this->roles);
        if (false !== $key = array_search(strtoupper($role), $roles, true)) {
            unset($roles[$key]);
            $roles = array_values($roles);
        }
        $this->roles = serialize($roles);
        return $this;
    }

    public function setSuperAdmin($boolean)
    {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    public function setRoles(array $roles)
    {
        $this->roles = serialize(array());

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->getGroup()->getName();
    }
}