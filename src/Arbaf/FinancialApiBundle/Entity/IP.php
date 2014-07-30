<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/30/14
 * Time: 2:01 AM
 */

namespace Arbaf\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="api_allowed_ips")
 */
class IP {


    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @ORM\Column(type="string")
     */
    private $address;


    /**
     * @ORM\ManyToOne(targetEntity="Arbaf\FinancialApiBundle\Entity\User")
     */
    private $user;

}